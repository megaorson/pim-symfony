<?php
declare(strict_types=1);

namespace App\Service\Product\Flat\Read;

use App\Exception\Api\InvalidFilterException;
use App\Service\Eav\Filter\Ast\ConditionNode;
use App\Service\Eav\Filter\Ast\GroupNode;
use App\Service\Eav\Filter\Ast\Node;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ProductFlatFilterSqlCompiler
{
    public function __construct(
        private ParsedFilterAstFactory $parsedFilterAstFactory,
        private ProductFlatFieldMap $fieldMap,
        private FilterValuePreparer $filterValuePreparer,
        private TranslatorInterface $translator,
    ) {
    }

    public function compile(?string $rawFilter, string $rootAlias = 'pf'): CompiledFilter
    {
        $ast = $this->parsedFilterAstFactory->create($rawFilter);

        if ($ast === null) {
            return new CompiledFilter('1 = 1', []);
        }

        $counter = 0;

        return $this->compileNode($ast, $rootAlias, $counter);
    }

    private function compileNode(Node $node, string $rootAlias, int &$counter): CompiledFilter
    {
        if ($node instanceof ConditionNode) {
            return $this->compileCondition($node, $rootAlias, $counter++);
        }

        if (!$node instanceof GroupNode) {
            throw new \RuntimeException(sprintf('Unsupported filter node "%s".', $node::class));
        }

        return match (strtoupper($node->type)) {
            'AND' => $this->compileGroup($node, 'AND', $rootAlias, $counter),
            'OR' => $this->compileGroup($node, 'OR', $rootAlias, $counter),
            default => throw new \RuntimeException(sprintf('Unsupported group type "%s".', $node->type)),
        };
    }

    private function compileGroup(
        GroupNode $group,
        string $glue,
        string $rootAlias,
        int &$counter,
    ): CompiledFilter {
        if ($group->children === []) {
            return new CompiledFilter($glue === 'AND' ? '1 = 1' : '1 = 0', []);
        }

        $parts = [];
        $parameters = [];

        foreach ($group->children as $child) {
            $compiled = $this->compileNode($child, $rootAlias, $counter);
            $parts[] = '(' . $compiled->sql . ')';
            $parameters = array_merge($parameters, $compiled->parameters);
        }

        return new CompiledFilter(
            implode(' ' . $glue . ' ', $parts),
            $parameters,
        );
    }

    private function compileCondition(
        ConditionNode $condition,
        string $rootAlias,
        int $index,
    ): CompiledFilter {
        $fieldToColumnMap = $this->fieldMap->getFilterFieldToColumnMap();
        $column = $fieldToColumnMap[$condition->field] ?? null;

        if ($column === null) {
            throw new InvalidFilterException(
                $this->translator->trans('eav.filter.unknown_field', ['%field%' => $condition->field])
            );
        }

        $fieldExpression = sprintf('%s.%s', $rootAlias, $column);
        $type = $this->fieldMap->getFieldType($condition->field);
        $operator = strtoupper($condition->operator);
        $paramBase = 'f_' . $index;

        $preparedValue = $this->filterValuePreparer->prepare(
            field: $condition->field,
            operator: $operator,
            rawValue: $condition->value,
            type: $type,
        );

        return $this->buildPredicate(
            fieldExpression: $fieldExpression,
            operator: $operator,
            preparedValue: $preparedValue,
            paramBase: $paramBase,
        );
    }

    private function buildPredicate(
        string $fieldExpression,
        string $operator,
        mixed $preparedValue,
        string $paramBase,
    ): CompiledFilter {
        return match ($operator) {
            'EQ' => new CompiledFilter(
                sprintf('%s = :%s', $fieldExpression, $paramBase),
                [$paramBase => $preparedValue],
            ),

            'NE' => new CompiledFilter(
                sprintf('(%s <> :%s OR %s IS NULL)', $fieldExpression, $paramBase, $fieldExpression),
                [$paramBase => $preparedValue],
            ),

            'GT' => new CompiledFilter(
                sprintf('%s > :%s', $fieldExpression, $paramBase),
                [$paramBase => $preparedValue],
            ),

            'GE' => new CompiledFilter(
                sprintf('%s >= :%s', $fieldExpression, $paramBase),
                [$paramBase => $preparedValue],
            ),

            'LT' => new CompiledFilter(
                sprintf('%s < :%s', $fieldExpression, $paramBase),
                [$paramBase => $preparedValue],
            ),

            'LE' => new CompiledFilter(
                sprintf('%s <= :%s', $fieldExpression, $paramBase),
                [$paramBase => $preparedValue],
            ),

            'BEGINS' => new CompiledFilter(
                sprintf('%s LIKE :%s', $fieldExpression, $paramBase),
                [$paramBase => $preparedValue],
            ),

            'IN' => $this->buildInPredicate($fieldExpression, $preparedValue, $paramBase),

            default => throw new \RuntimeException(sprintf('Unsupported operator "%s".', $operator)),
        };
    }

    /**
     * @param list<mixed> $preparedValue
     */
    private function buildInPredicate(
        string $fieldExpression,
        array $preparedValue,
        string $paramBase,
    ): CompiledFilter {
        if ($preparedValue === []) {
            return new CompiledFilter('1 = 0', []);
        }

        $placeholders = [];
        $parameters = [];

        foreach (array_values($preparedValue) as $i => $value) {
            $paramName = $paramBase . '_' . $i;
            $placeholders[] = ':' . $paramName;
            $parameters[$paramName] = $value;
        }

        return new CompiledFilter(
            sprintf('%s IN (%s)', $fieldExpression, implode(', ', $placeholders)),
            $parameters,
        );
    }
}
