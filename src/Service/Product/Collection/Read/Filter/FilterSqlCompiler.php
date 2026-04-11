<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Exception\Api\InvalidFilterException;
use App\Service\Eav\AttributeTypeRegistry;
use App\Service\Eav\Dto\AttributeMetadata;
use App\Service\Eav\Filter\Ast\ConditionNode;
use App\Service\Eav\Filter\Ast\GroupNode;
use App\Service\Eav\Filter\Ast\Node;
use App\Service\ProductAttributeValue\ClassNameToTableName;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class FilterSqlCompiler
{
    public function __construct(
        private FilterFieldResolver $fieldResolver,
        private AttributeTypeRegistry $attributeTypeRegistry,
        private ClassNameToTableName $classNameToTableName,
        private TranslatorInterface $translator,
    ) {
    }

    public function compile(Node $node): CompiledFilter
    {
        $counter = 0;

        return $this->compileNode($node, $counter);
    }

    private function compileNode(Node $node, int &$counter): CompiledFilter
    {
        if ($node instanceof ConditionNode) {
            return $this->compileComparison(
                field: $node->field,
                operator: $node->operator,
                value: $node->value,
                index: $counter++,
            );
        }

        if ($node instanceof GroupNode) {
            return $this->compileGroup($node, $counter);
        }

        throw new \RuntimeException(sprintf('Unsupported filter node "%s".', $node::class));
    }

    private function compileGroup(GroupNode $node, int &$counter): CompiledFilter
    {
        if ($node->children === []) {
            return CompiledFilter::empty();
        }

        $parts = [];
        $parameters = [];

        foreach ($node->children as $child) {
            $compiled = $this->compileNode($child, $counter);

            if ($compiled->isEmpty()) {
                continue;
            }

            $parts[] = '(' . $compiled->sql . ')';
            $parameters = array_merge($parameters, $compiled->parameters);
        }

        if ($parts === []) {
            return CompiledFilter::empty();
        }

        $glue = strtoupper($node->type);

        if (!in_array($glue, ['AND', 'OR'], true)) {
            throw new \RuntimeException(sprintf('Unsupported group type "%s".', $glue));
        }

        return new CompiledFilter(
            implode(' ' . $glue . ' ', $parts),
            $parameters,
        );
    }

    /**
     * @param mixed $value
     */
    public function compileComparison(string $field, string $operator, mixed $value, int $index = 0): CompiledFilter
    {
        $definition = $this->fieldResolver->resolve($field);

        if ($definition->isSystemField) {
            return $this->compileSystemComparison(
                column: $definition->systemColumn ?? $field,
                field: $field,
                operator: $operator,
                value: $value,
                index: $index,
            );
        }

        $metadata = $definition->attributeMetadata;
        if (!$metadata instanceof AttributeMetadata) {
            throw new \RuntimeException(sprintf('Attribute metadata is required for field "%s".', $field));
        }

        $tableName = $this->resolveTableNameByType($metadata->type);
        $paramBase = 'f_' . $index;
        $preparedValue = $this->prepareValue($field, $operator, $value, $metadata->type);

        [$valueSql, $parameters] = $this->compileValuePredicate(
            fieldExpression: 'v.value',
            operator: $operator,
            value: $preparedValue,
            paramBase: $paramBase,
        );

        $sql = sprintf(
            'EXISTS (
                SELECT 1
                FROM %s v
                WHERE v.product_id = p.id
                  AND v.attribute_id = :%s_attr
                  AND %s
            )',
            $tableName,
            $paramBase,
            $valueSql
        );

        $parameters[$paramBase . '_attr'] = $metadata->id;

        return new CompiledFilter($sql, $parameters);
    }

    /**
     * @param mixed $value
     */
    private function compileSystemComparison(
        string $column,
        string $field,
        string $operator,
        mixed $value,
        int $index
    ): CompiledFilter {
        $paramBase = 'f_' . $index;
        $preparedValue = $this->prepareValue($field, $operator, $value, $this->resolveBaseFieldType($field));

        [$sql, $parameters] = $this->compileValuePredicate(
            fieldExpression: 'p.' . $column,
            operator: $operator,
            value: $preparedValue,
            paramBase: $paramBase,
        );

        return new CompiledFilter($sql, $parameters);
    }

    /**
     * @param mixed $value
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function compileValuePredicate(
        string $fieldExpression,
        string $operator,
        mixed $value,
        string $paramBase,
    ): array {
        return match ($operator) {
            'EQ' => [
                sprintf('%s = :%s', $fieldExpression, $paramBase),
                [$paramBase => $value],
            ],
            'NE' => [
                sprintf('%s != :%s', $fieldExpression, $paramBase),
                [$paramBase => $value],
            ],
            'GT' => [
                sprintf('%s > :%s', $fieldExpression, $paramBase),
                [$paramBase => $value],
            ],
            'GE' => [
                sprintf('%s >= :%s', $fieldExpression, $paramBase),
                [$paramBase => $value],
            ],
            'LT' => [
                sprintf('%s < :%s', $fieldExpression, $paramBase),
                [$paramBase => $value],
            ],
            'LE' => [
                sprintf('%s <= :%s', $fieldExpression, $paramBase),
                [$paramBase => $value],
            ],
            'BEGINS' => [
                sprintf('%s LIKE :%s', $fieldExpression, $paramBase),
                [$paramBase => $value],
            ],
            'IN' => $this->compileInPredicate($fieldExpression, $value, $paramBase),
            default => throw new InvalidFilterException(
                $this->translator->trans('eav.filter.unsupported_operator', ['%operator%' => $operator])
            ),
        };
    }

    /**
     * @param mixed $value
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function compileInPredicate(string $fieldExpression, mixed $value, string $paramBase): array
    {
        if (!is_array($value) || $value === []) {
            throw new InvalidFilterException(
                $this->translator->trans('eav.filter.invalid_in_value', ['%value%' => is_scalar($value) ? (string) $value : get_debug_type($value)])
            );
        }

        $placeholders = [];
        $parameters = [];

        foreach (array_values($value) as $index => $item) {
            $paramName = $paramBase . '_' . $index;
            $placeholders[] = ':' . $paramName;
            $parameters[$paramName] = $item;
        }

        return [
            sprintf('%s IN (%s)', $fieldExpression, implode(', ', $placeholders)),
            $parameters,
        ];
    }

    /**
     * @param mixed $rawValue
     */
    private function prepareValue(string $field, string $operator, mixed $rawValue, string $type): mixed
    {
        if (!is_string($rawValue)) {
            if ($operator === 'IN' && is_array($rawValue)) {
                return $rawValue;
            }

            return $rawValue;
        }

        if ($operator === 'IN') {
            $values = $this->parseInValues($rawValue, $type);

            if ($values === []) {
                throw new InvalidFilterException(
                    $this->translator->trans('eav.filter.empty_in_values', ['%field%' => $field])
                );
            }

            return $values;
        }

        $value = $this->normalizeScalarValue($rawValue, $type);

        if (in_array($operator, ['GT', 'GE', 'LT', 'LE'], true)) {
            $this->assertComparableScalarType($field, $operator, $type);
        }

        if ($operator === 'BEGINS') {
            return $this->escapeLike((string) $value) . '%';
        }

        return $value;
    }

    /**
     * @return list<int|float|string>
     */
    private function parseInValues(string $value, string $type): array
    {
        $value = trim($value);

        if (!str_starts_with($value, '(') || !str_ends_with($value, ')')) {
            throw new InvalidFilterException(
                $this->translator->trans('eav.filter.invalid_in_value', ['%value%' => $value])
            );
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

    /**
     * @return list<string>
     */
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

            if ($char === '\'' || $char === '"') {
                $inQuote = true;
                $quoteChar = $char;
                $buffer .= $char;
                continue;
            }

            if ($char === ',') {
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

    private function assertComparableScalarType(string $field, string $operator, string $type): void
    {
        if (!in_array($type, ['int', 'decimal', 'float', 'boolean'], true)) {
            throw new InvalidFilterException(
                $this->translator->trans('eav.filter.non_numeric_operator', [
                    '%operator%' => $operator,
                    '%field%' => $field,
                    '%type%' => $type,
                ])
            );
        }
    }

    private function trimWrappingQuotes(string $value): string
    {
        $value = trim($value);
        $length = strlen($value);

        if ($length >= 2) {
            $first = $value[0];
            $last = $value[$length - 1];

            if (($first === '\'' && $last === '\'') || ($first === '"' && $last === '"')) {
                $value = substr($value, 1, -1);
            }
        }

        return stripcslashes($value);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function resolveBaseFieldType(string $field): string
    {
        return match ($field) {
            'id' => 'int',
            default => 'string',
        };
    }

    private function resolveTableNameByType(string $type): string
    {
        return $this->classNameToTableName->execute(
            $this->attributeTypeRegistry->getValueEntityClass($type)
        );
    }
}
