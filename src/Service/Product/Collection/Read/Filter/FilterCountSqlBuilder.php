<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Eav\Filter\Ast\ConditionNode;
use App\Service\Eav\Filter\Ast\GroupNode;
use App\Service\Eav\Filter\Ast\Node;

final readonly class FilterCountSqlBuilder
{
    public function __construct(
        private FilterConditionSourceFactory $conditionSourceFactory,
    ) {
    }

    public function buildCount(Node $node): CompiledFilter
    {
        $counter = 0;
        $source = $this->buildProductIdSource($node, $counter);

        return new CompiledFilter(
            sprintf('SELECT COUNT(*) FROM (%s) count_src', $source->sql),
            $source->parameters,
        );
    }

    private function buildProductIdSource(Node $node, int &$counter): CompiledFilter
    {
        if ($node instanceof ConditionNode) {
            return $this->buildConditionSource($node, $counter++);
        }

        if (!$node instanceof GroupNode) {
            throw new \RuntimeException(sprintf('Unsupported filter node "%s".', $node::class));
        }

        return match (strtoupper($node->type)) {
            'AND' => $this->buildAndSource($node, $counter),
            'OR' => $this->buildOrSource($node, $counter),
            default => throw new \RuntimeException(sprintf('Unsupported group type "%s".', $node->type)),
        };
    }

    private function buildAndSource(GroupNode $group, int &$counter): CompiledFilter
    {
        if ($group->children === []) {
            return new CompiledFilter('SELECT p.id AS product_id FROM product p', []);
        }

        $sources = [];
        foreach ($group->children as $child) {
            $sources[] = $this->buildProductIdSource($child, $counter);
        }

        $baseAlias = 's0';
        $from = sprintf('(%s) %s', $sources[0]->sql, $baseAlias);
        $parameters = $sources[0]->parameters;
        $joins = [];

        for ($i = 1, $n = count($sources); $i < $n; $i++) {
            $alias = 's' . $i;

            $joins[] = sprintf(
                'INNER JOIN (%s) %s ON %s.product_id = %s.product_id',
                $sources[$i]->sql,
                $alias,
                $alias,
                $baseAlias
            );

            $parameters = array_merge($parameters, $sources[$i]->parameters);
        }

        return new CompiledFilter(
            sprintf(
                'SELECT DISTINCT %s.product_id FROM %s %s',
                $baseAlias,
                $from,
                implode(' ', $joins)
            ),
            $parameters
        );
    }

    private function buildOrSource(GroupNode $group, int &$counter): CompiledFilter
    {
        if ($group->children === []) {
            return new CompiledFilter('SELECT p.id AS product_id FROM product p WHERE 1 = 0', []);
        }

        $parts = [];
        $parameters = [];

        foreach ($group->children as $child) {
            $compiled = $this->buildProductIdSource($child, $counter);
            $parts[] = $compiled->sql;
            $parameters = array_merge($parameters, $compiled->parameters);
        }

        return new CompiledFilter(
            implode(' UNION DISTINCT ', $parts),
            $parameters
        );
    }

    private function buildConditionSource(ConditionNode $condition, int $index): CompiledFilter
    {
        $source = $this->conditionSourceFactory->createForCount(
            condition: $condition,
            paramBase: 'c_' . $index,
        );

        return new CompiledFilter($source->sql, $source->parameters);
    }
}
