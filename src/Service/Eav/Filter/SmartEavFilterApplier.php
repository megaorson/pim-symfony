<?php
declare(strict_types=1);

namespace App\Service\Eav\Filter;

use App\Service\Eav\AttributeMetadataProvider;
use App\Service\Eav\AttributeTypeRegistry;
use App\Service\Eav\Dto\AttributeMetadata;
use App\Service\Eav\Filter\Ast\ConditionNode;
use App\Service\Eav\Filter\Ast\GroupNode;
use App\Service\Eav\Filter\Ast\Node;
use App\Service\Product\Collection\CollectionApplierInterface;
use App\Service\Product\Collection\ProductCollectionContext;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AutoconfigureTag('app.product.collection_applier')]
#[AsTaggedItem(priority: 100)]
final class SmartEavFilterApplier implements CollectionApplierInterface
{
    private int $joinIndex = 0;
    private int $paramIndex = 0;

    /** @var array<string, string> */
    private array $aliases = [];

    /** @var array<string, string> */
    private const BASE_FIELDS = [
        'id' => 'int',
        'sku' => 'string',
    ];

    public function __construct(
        private readonly Parser $parser,
        private readonly FieldCollector $fieldCollector,
        private readonly AttributeMetadataProvider $metadataProvider,
        private readonly AttributeTypeRegistry $typeRegistry,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function apply(QueryBuilder $qb, ProductCollectionContext|string $context, string $rootAlias = 'p'): void
    {
        $filter = $context instanceof ProductCollectionContext ? $context->filter : $context;

        if (!is_string($filter)) {
            return;
        }

        $this->resetState();

        $filter = trim($filter);

        if ($filter === '') {
            return;
        }

        $ast = $this->parser->parse($filter);
        $codes = $this->fieldCollector->collect($ast);
        $metadataMap = $this->metadataProvider->getByCodes($codes);

        foreach ($codes as $code) {
            if (!isset($metadataMap[$code]) && !isset(self::BASE_FIELDS[$code])) {
                throw new \InvalidArgumentException($this->translator->trans('eav.filter.unknown_field', ['%field%' => $code]));
            }
        }

        $expression = $this->buildNodeExpression($qb, $ast, $metadataMap, $rootAlias, false);

        if ($expression !== null) {
            $qb->andWhere($expression);
        }
    }

    private function resetState(): void
    {
        $this->joinIndex = 0;
        $this->paramIndex = 0;
        $this->aliases = [];
    }

    /** @param array<string, AttributeMetadata> $metadataMap */
    private function buildNodeExpression(QueryBuilder $qb, Node $node, array $metadataMap, string $rootAlias, bool $insideOr): string|Andx|Orx|null
    {
        if ($node instanceof ConditionNode) {
            if (isset(self::BASE_FIELDS[$node->field])) {
                return $this->buildBaseFieldCondition($qb, $node, $rootAlias, self::BASE_FIELDS[$node->field]);
            }

            $metadata = $metadataMap[$node->field];

            return $insideOr
                ? $this->buildExistsCondition($qb, $node, $metadata, $rootAlias)
                : $this->buildJoinCondition($qb, $node, $metadata, $rootAlias);
        }

        if (!$node instanceof GroupNode) {
            return null;
        }

        $parts = [];
        $childInsideOr = $insideOr || $node->type === 'OR';

        foreach ($node->children as $child) {
            $expr = $this->buildNodeExpression($qb, $child, $metadataMap, $rootAlias, $childInsideOr);
            if ($expr !== null) {
                $parts[] = $expr;
            }
        }

        if ($parts === []) {
            return null;
        }

        return $node->type === 'OR' ? new Orx($parts) : new Andx($parts);
    }

    private function buildBaseFieldCondition(QueryBuilder $qb, ConditionNode $condition, string $rootAlias, string $type): string
    {
        $fieldPath = $rootAlias . '.' . $condition->field;
        $paramName = 'base_' . $this->paramIndex++;
        $preparedValue = $this->prepareBaseFieldValue($condition, $type);

        $qb->setParameter($paramName, $preparedValue);

        return $this->buildComparisonExpressionRaw($condition->operator, $fieldPath, ':' . $paramName);
    }

    private function prepareBaseFieldValue(ConditionNode $condition, string $type): mixed
    {
        if ($condition->operator === 'IN') {
            $values = $this->parseInValues($condition->value, $type);
            if ($values === []) {
                throw new \InvalidArgumentException($this->translator->trans('eav.filter.empty_in_values', ['%field%' => $condition->field]));
            }
            return $values;
        }

        $value = $this->normalizeScalarValue($condition->value, $type);

        if (in_array($condition->operator, ['GT', 'GE', 'LT', 'LE'], true)) {
            $this->assertComparableScalarType($condition->field, $condition->operator, $type);
        }

        if ($condition->operator === 'BEGINS') {
            return $this->escapeLike((string) $value) . '%';
        }

        return $value;
    }

    private function buildJoinCondition(QueryBuilder $qb, ConditionNode $condition, AttributeMetadata $metadata, string $rootAlias): string
    {
        $alias = $this->ensureJoin($qb, $metadata, $rootAlias);
        return $this->buildComparisonExpression($qb, $alias . '.value', $condition, $metadata);
    }

    private function buildExistsCondition(QueryBuilder $qb, ConditionNode $condition, AttributeMetadata $metadata, string $rootAlias): string
    {
        $subAlias = 'sx' . $this->joinIndex++;
        $entityClass = $this->typeRegistry->getValueEntityClass($metadata->type);
        $attributeParam = 'exists_attr_' . $this->paramIndex++;
        $valueParam = 'exists_val_' . $this->paramIndex++;
        $preparedValue = $this->prepareValue($condition, $metadata);

        $qb->setParameter($attributeParam, $metadata->id);
        $qb->setParameter($valueParam, $preparedValue);

        $comparison = $this->buildComparisonExpressionRaw($condition->operator, $subAlias . '.value', ':' . $valueParam);

        return sprintf('EXISTS (SELECT 1 FROM %s %s WHERE %s.product = %s AND %s.attribute = :%s AND %s)', $entityClass, $subAlias, $subAlias, $rootAlias, $subAlias, $attributeParam, $comparison);
    }

    private function buildComparisonExpression(QueryBuilder $qb, string $fieldPath, ConditionNode $condition, AttributeMetadata $metadata): string
    {
        $paramName = 'filter_' . $this->paramIndex++;
        $preparedValue = $this->prepareValue($condition, $metadata);
        $qb->setParameter($paramName, $preparedValue);
        return $this->buildComparisonExpressionRaw($condition->operator, $fieldPath, ':' . $paramName);
    }

    private function buildComparisonExpressionRaw(string $operator, string $fieldPath, string $parameterName): string
    {
        return match ($operator) {
            'EQ' => sprintf('%s = %s', $fieldPath, $parameterName),
            'NE' => sprintf('%s != %s', $fieldPath, $parameterName),
            'GT' => sprintf('%s > %s', $fieldPath, $parameterName),
            'GE' => sprintf('%s >= %s', $fieldPath, $parameterName),
            'LT' => sprintf('%s < %s', $fieldPath, $parameterName),
            'LE' => sprintf('%s <= %s', $fieldPath, $parameterName),
            'BEGINS' => sprintf('%s LIKE %s', $fieldPath, $parameterName),
            'IN' => sprintf('%s IN (%s)', $fieldPath, $parameterName),
            default => throw new \InvalidArgumentException($this->translator->trans('eav.filter.unsupported_operator', ['%operator%' => $operator])),
        };
    }

    private function ensureJoin(QueryBuilder $qb, AttributeMetadata $metadata, string $rootAlias): string
    {
        if (isset($this->aliases[$metadata->code])) {
            return $this->aliases[$metadata->code];
        }

        $alias = 'j' . $this->joinIndex++;
        $attributeParam = 'join_attr_' . $this->paramIndex++;
        $entityClass = $this->typeRegistry->getValueEntityClass($metadata->type);

        $qb->innerJoin($entityClass, $alias, 'WITH', sprintf('%s.product = %s AND %s.attribute = :%s', $alias, $rootAlias, $alias, $attributeParam));
        $qb->setParameter($attributeParam, $metadata->id);
        $this->aliases[$metadata->code] = $alias;

        return $alias;
    }

    private function prepareValue(ConditionNode $condition, AttributeMetadata $metadata): mixed
    {
        if ($condition->operator === 'IN') {
            $values = $this->parseInValues($condition->value, $metadata->type);
            if ($values === []) {
                throw new \InvalidArgumentException($this->translator->trans('eav.filter.empty_in_values', ['%field%' => $condition->field]));
            }
            return $values;
        }

        $value = $this->normalizeScalarValue($condition->value, $metadata->type);
        if (in_array($condition->operator, ['GT', 'GE', 'LT', 'LE'], true)) {
            $this->assertComparableType($condition, $metadata);
        }
        if ($condition->operator === 'BEGINS') {
            return $this->escapeLike((string) $value) . '%';
        }
        return $value;
    }

    /** @return list<int|float|string> */
    private function parseInValues(string $value, string $type): array
    {
        $value = trim($value);
        if (!str_starts_with($value, '(') || !str_ends_with($value, ')')) {
            throw new \InvalidArgumentException($this->translator->trans('eav.filter.invalid_in_value', ['%value%' => $value]));
        }

        $inner = trim(substr($value, 1, -1));
        if ($inner === '') {
            return [];
        }

        $items = $this->splitInValues($inner);
        $result = [];
        foreach ($items as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            $result[] = $this->normalizeScalarValue($this->trimWrappingQuotes($item), $type);
        }
        return array_values($result);
    }

    /** @return list<string> */
    private function splitInValues(string $input): array
    {
        $items = [];
        $buffer = '';
        $length = strlen($input);
        $inQuote = false;
        $quoteChar = null;

        for ($i = 0; $i < $length; $i++) {
            $char = $input[$i];
            if ($inQuote) {
                if ($char === '\\' && isset($input[$i + 1])) {
                    $buffer .= $input[$i + 1];
                    $i++;
                    continue;
                }
                if ($char === $quoteChar) {
                    $inQuote = false;
                    $quoteChar = null;
                }
                $buffer .= $char;
                continue;
            }
            if ($char === "'" || $char === '"') {
                $inQuote = true;
                $quoteChar = $char;
                $buffer .= $char;
                continue;
            }
            if ($char === ';') {
                $items[] = $buffer;
                $buffer = '';
                continue;
            }
            $buffer .= $char;
        }
        if ($buffer !== '') {
            $items[] = $buffer;
        }
        return array_values($items);
    }

    private function normalizeScalarValue(string $value, string $type): int|float|string
    {
        $value = trim($value);
        return match ($type) {
            'int', 'boolean' => (int) $value,
            'decimal', 'float' => (float) $value,
            default => $this->trimWrappingQuotes($value),
        };
    }

    private function assertComparableType(ConditionNode $condition, AttributeMetadata $metadata): void
    {
        $this->assertComparableScalarType($condition->field, $condition->operator, $metadata->type);
    }

    private function assertComparableScalarType(string $field, string $operator, string $type): void
    {
        if (!in_array($type, ['int', 'decimal', 'float', 'boolean'], true)) {
            throw new \InvalidArgumentException($this->translator->trans('eav.filter.non_numeric_operator', ['%operator%' => $operator, '%field%' => $field, '%type%' => $type]));
        }
    }

    private function trimWrappingQuotes(string $value): string
    {
        $value = trim($value);
        $length = strlen($value);
        if ($length >= 2) {
            $first = $value[0];
            $last = $value[$length - 1];
            if (($first === "'" && $last === "'") || ($first === '"' && $last === '"')) {
                $value = substr($value, 1, -1);
            }
        }
        return stripcslashes($value);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
