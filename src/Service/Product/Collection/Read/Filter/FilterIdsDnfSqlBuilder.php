<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Exception\Api\InvalidFilterException;
use App\Service\Eav\AttributeTypeRegistry;
use App\Service\Eav\Dto\AttributeMetadata;
use App\Service\Eav\Filter\Ast\ConditionNode;
use App\Service\ProductAttributeValue\ClassNameToTableName;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class FilterIdsDnfSqlBuilder
{
    public function __construct(
        private FilterFieldResolver $fieldResolver,
        private AttributeTypeRegistry $attributeTypeRegistry,
        private ClassNameToTableName $classNameToTableName,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param list<DnfBranch> $branches
     */
    public function build(array $branches): OptimizedFilterSource
    {
        $branchSql = [];
        $parameters = [];
        $counter = 0;
        $allProjectedFields = $this->findCommonProjectedFields($branches);

        foreach ($branches as $branchIndex => $branch) {
            $compiled = $this->buildBranch($branch, $branchIndex, $counter, $allProjectedFields);
            $branchSql[] = $compiled->sql;
            $parameters = array_merge($parameters, $compiled->parameters);
        }

        return new OptimizedFilterSource(
            sql: implode(' UNION DISTINCT ', $branchSql),
            parameters: $parameters,
            projectedColumnsByField: $allProjectedFields,
        );
    }

    /**
     * @param array<string, string> $projectedFields
     */
    private function buildBranch(
        DnfBranch $branch,
        int $branchIndex,
        int &$counter,
        array $projectedFields,
    ): OptimizedFilterSource {
        if ($branch->conditions === []) {
            return new OptimizedFilterSource(
                sql: 'SELECT p.id AS product_id FROM product p',
                parameters: [],
                projectedColumnsByField: [],
            );
        }

        $sources = [];
        $parameters = [];

        foreach ($branch->conditions as $condition) {
            $compiled = $this->buildConditionSource($condition, $branchIndex, $counter++);
            $sources[] = $compiled;
            $parameters = array_merge($parameters, $compiled->parameters);
        }

        $baseAlias = 'b' . $branchIndex . '_0';
        $from = sprintf('(%s) %s', $sources[0]->sql, $baseAlias);
        $joins = [];

        for ($i = 1, $n = count($sources); $i < $n; $i++) {
            $alias = 'b' . $branchIndex . '_' . $i;

            $joins[] = sprintf(
                'INNER JOIN (%s) %s ON %s.product_id = %s.product_id',
                $sources[$i]->sql,
                $alias,
                $alias,
                $baseAlias
            );
        }

        $selects = [sprintf('%s.product_id', $baseAlias)];

        foreach ($projectedFields as $field => $projectedColumn) {
            $conditionIndex = $this->findConditionIndexForField($branch, $field);

            if ($conditionIndex === null) {
                $selects[] = sprintf('NULL AS %s', $projectedColumn);
                continue;
            }

            $alias = 'b' . $branchIndex . '_' . $conditionIndex;
            $sourceProjectedColumn = $this->makeProjectedColumnName($field);

            $selects[] = sprintf('%s.%s AS %s', $alias, $sourceProjectedColumn, $projectedColumn);
        }

        $sql = sprintf(
            'SELECT DISTINCT %s FROM %s %s',
            implode(', ', $selects),
            $from,
            implode(' ', $joins)
        );

        return new OptimizedFilterSource(
            sql: $sql,
            parameters: $parameters,
            projectedColumnsByField: $projectedFields,
        );
    }

    private function findConditionIndexForField(DnfBranch $branch, string $field): ?int
    {
        foreach ($branch->conditions as $index => $condition) {
            if ($condition->field === $field) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param list<DnfBranch> $branches
     * @return array<string, string>
     */
    private function findCommonProjectedFields(array $branches): array
    {
        if ($branches === []) {
            return [];
        }

        $fieldCounts = [];
        $branchCount = count($branches);

        foreach ($branches as $branch) {
            $seen = [];

            foreach ($branch->conditions as $condition) {
                $field = $condition->field;

                if (isset($seen[$field])) {
                    continue;
                }

                $seen[$field] = true;
                $fieldCounts[$field] = ($fieldCounts[$field] ?? 0) + 1;
            }
        }

        $result = [];

        foreach ($fieldCounts as $field => $count) {
            if ($count === $branchCount) {
                $result[$field] = $this->makeProjectedColumnName($field);
            }
        }

        return $result;
    }

    private function buildConditionSource(ConditionNode $condition, int $branchIndex, int $index): OptimizedFilterSource
    {
        $definition = $this->fieldResolver->resolve($condition->field);
        $paramBase = 'd_' . $branchIndex . '_' . $index;
        $projectedColumn = $this->makeProjectedColumnName($condition->field);

        if ($definition->isSystemField) {
            $column = $definition->systemColumn ?? $condition->field;
            $type = $this->resolveBaseFieldType($condition->field);
            $preparedValue = $this->prepareValue(
                $condition->field,
                $condition->operator,
                $condition->value,
                $type
            );

            [$predicateSql, $parameters] = $this->compilePredicate(
                'p.' . $column,
                $condition->operator,
                $preparedValue,
                $paramBase
            );

            return new OptimizedFilterSource(
                sql: sprintf(
                    'SELECT p.id AS product_id, p.%s AS %s FROM product p WHERE %s',
                    $column,
                    $projectedColumn,
                    $predicateSql
                ),
                parameters: $parameters,
                projectedColumnsByField: [$condition->field => $projectedColumn],
            );
        }

        $metadata = $definition->attributeMetadata;
        if (!$metadata instanceof AttributeMetadata) {
            throw new \RuntimeException(sprintf('Attribute metadata is required for field "%s".', $condition->field));
        }

        $tableName = $this->resolveTableNameByType($metadata->type);
        $preparedValue = $this->prepareValue(
            $condition->field,
            $condition->operator,
            $condition->value,
            $metadata->type
        );

        [$predicateSql, $parameters] = $this->compilePredicate(
            'v.value',
            $condition->operator,
            $preparedValue,
            $paramBase
        );

        $parameters[$paramBase . '_attr'] = $metadata->id;

        return new OptimizedFilterSource(
            sql: sprintf(
                'SELECT v.product_id, v.value AS %s
                 FROM %s v
                 WHERE v.attribute_id = :%s
                   AND %s',
                $projectedColumn,
                $tableName,
                $paramBase . '_attr',
                $predicateSql
            ),
            parameters: $parameters,
            projectedColumnsByField: [$condition->field => $projectedColumn],
        );
    }

    private function makeProjectedColumnName(string $field): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]+/', '_', $field) ?? $field;
        return 'sort_' . strtolower($safe);
    }

    /**
     * @param mixed $preparedValue
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function compilePredicate(
        string $fieldExpression,
        string $operator,
        mixed $preparedValue,
        string $paramBase,
    ): array {
        return match ($operator) {
            'EQ' => [sprintf('%s = :%s', $fieldExpression, $paramBase), [$paramBase => $preparedValue]],
            'NE' => [sprintf('%s != :%s', $fieldExpression, $paramBase), [$paramBase => $preparedValue]],
            'GT' => [sprintf('%s > :%s', $fieldExpression, $paramBase), [$paramBase => $preparedValue]],
            'GE' => [sprintf('%s >= :%s', $fieldExpression, $paramBase), [$paramBase => $preparedValue]],
            'LT' => [sprintf('%s < :%s', $fieldExpression, $paramBase), [$paramBase => $preparedValue]],
            'LE' => [sprintf('%s <= :%s', $fieldExpression, $paramBase), [$paramBase => $preparedValue]],
            'BEGINS' => [sprintf('%s LIKE :%s', $fieldExpression, $paramBase), [$paramBase => $preparedValue]],
            'IN' => $this->compileInPredicate($fieldExpression, $preparedValue, $paramBase),
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
                $this->translator->trans('eav.filter.invalid_in_value', [
                    '%value%' => is_scalar($value) ? (string) $value : get_debug_type($value),
                ])
            );
        }

        $placeholders = [];
        $parameters = [];

        foreach (array_values($value) as $i => $item) {
            $param = $paramBase . '_' . $i;
            $placeholders[] = ':' . $param;
            $parameters[$param] = $item;
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
