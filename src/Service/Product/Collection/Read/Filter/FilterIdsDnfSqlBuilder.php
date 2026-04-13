<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

final readonly class FilterIdsDnfSqlBuilder
{
    public function __construct(
        private FilterConditionSourceFactory $filterConditionSourceFactory,
    ) {
    }

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

        return new OptimizedFilterSource(implode(' UNION DISTINCT ', $branchSql), $parameters, $allProjectedFields);
    }

    private function buildBranch(DnfBranch $branch, int $branchIndex, int &$counter, array $projectedFields): OptimizedFilterSource
    {
        if ($branch->conditions === []) {
            return new OptimizedFilterSource('SELECT p.id AS product_id FROM product p', [], []);
        }

        $sources = [];
        $parameters = [];
        foreach ($branch->conditions as $condition) {
            $source = $this->filterConditionSourceFactory->createProductIdSource($condition, 'd_' . $branchIndex . '_' . $counter++);
            $sources[] = $source;
            $parameters = array_merge($parameters, $source->parameters);
        }

        $baseAlias = 'b' . $branchIndex . '_0';
        $from = sprintf('(%s) %s', $sources[0]->sql, $baseAlias);
        $joins = [];
        for ($i = 1, $n = count($sources); $i < $n; $i++) {
            $alias = 'b' . $branchIndex . '_' . $i;
            $joins[] = sprintf('INNER JOIN (%s) %s ON %s.product_id = %s.product_id', $sources[$i]->sql, $alias, $alias, $baseAlias);
        }

        $selects = [sprintf('%s.product_id', $baseAlias)];
        foreach ($projectedFields as $field => $projectedColumn) {
            $conditionIndex = $this->findConditionIndexForField($branch, $field);
            if ($conditionIndex === null) {
                $selects[] = sprintf('NULL AS %s', $projectedColumn);
                continue;
            }
            $alias = 'b' . $branchIndex . '_' . $conditionIndex;
            $sourceProjectedColumn = $this->filterConditionSourceFactory->makeProjectedColumnName($field);
            $selects[] = sprintf('%s.%s AS %s', $alias, $sourceProjectedColumn, $projectedColumn);
        }

        return new OptimizedFilterSource(
            sprintf('SELECT DISTINCT %s FROM %s %s', implode(', ', $selects), $from, implode(' ', $joins)),
            $parameters,
            $projectedFields,
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
                $result[$field] = $this->filterConditionSourceFactory->makeProjectedColumnName($field);
            }
        }
        return $result;
    }
}
