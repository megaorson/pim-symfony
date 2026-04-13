<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Eav\Filter\Ast\ConditionNode;
use App\Service\Eav\Filter\Ast\GroupNode;
use App\Service\Eav\Filter\Ast\Node;

final readonly class FilterSqlCompiler
{
    public function __construct(
        private FilterConditionSourceFactory $filterConditionSourceFactory,
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
            return $this->filterConditionSourceFactory->createExistsCondition($node, $counter++);
        }

        if (!$node instanceof GroupNode) {
            throw new \RuntimeException(sprintf('Unsupported filter node "%s".', $node::class));
        }

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

        return new CompiledFilter(implode(' ' . strtoupper($node->type) . ' ', $parts), $parameters);
    }
}
