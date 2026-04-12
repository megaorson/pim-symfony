<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Eav\AttributeTypeRegistry;
use App\Service\Eav\Dto\AttributeMetadata;
use App\Service\Eav\Filter\Ast\ConditionNode;
use App\Service\ProductAttributeValue\ClassNameToTableName;

final readonly class FilterIdsDnfSqlBuilder
{
    public function __construct(
        private FilterFieldResolver $fieldResolver,
        private AttributeTypeRegistry $attributeTypeRegistry,
        private ClassNameToTableName $classNameToTableName,
        private FilterValuePreparer $filterValuePreparer,
        private SqlPredicateBuilder $sqlPredicateBuilder,
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
     * @param array<string,string> $projectedFields
     */
    private function buildBranch(DnfBranch $branch, int $branchIndex, int &$counter, array $projectedFields): OptimizedFilterSource
    {
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
                $baseAlias,
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
            implode(' ', $joins),
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
     * @return array<string,string>
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
            $preparedValue = $this->filterValuePreparer->prepare(
                $condition->field,
                $condition->operator,
                $condition->value,
                $this->filterValuePreparer->resolveBaseFieldType($condition->field),
            );

            [$predicateSql, $parameters] = $this->sqlPredicateBuilder->build(
                'p.' . $column,
                $condition->operator,
                $preparedValue,
                $paramBase,
            );

            return new OptimizedFilterSource(
                sql: sprintf(
                    'SELECT p.id AS product_id, p.%s AS %s FROM product p WHERE %s',
                    $column,
                    $projectedColumn,
                    $predicateSql,
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
        $preparedValue = $this->filterValuePreparer->prepare(
            $condition->field,
            $condition->operator,
            $condition->value,
            $metadata->type,
        );

        [$predicateSql, $parameters] = $this->sqlPredicateBuilder->build(
            'v.value',
            $condition->operator,
            $preparedValue,
            $paramBase,
        );

        $parameters[$paramBase . '_attr'] = $metadata->id;

        return new OptimizedFilterSource(
            sql: sprintf(
                'SELECT v.product_id, v.value AS %s FROM %s v WHERE v.attribute_id = :%s AND %s',
                $projectedColumn,
                $tableName,
                $paramBase . '_attr',
                $predicateSql,
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

    private function resolveTableNameByType(string $type): string
    {
        return $this->classNameToTableName->execute(
            $this->attributeTypeRegistry->getValueEntityClass($type),
        );
    }
}
