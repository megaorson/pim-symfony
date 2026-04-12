<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Eav\AttributeTypeRegistry;
use App\Service\Eav\Dto\AttributeMetadata;
use App\Service\Eav\Filter\Ast\ConditionNode;
use App\Service\Eav\Filter\Ast\GroupNode;
use App\Service\Eav\Filter\Ast\Node;
use App\Service\ProductAttributeValue\ClassNameToTableName;

final readonly class FilterIdsSqlBuilder
{
    public function __construct(
        private FilterFieldResolver $fieldResolver,
        private AttributeTypeRegistry $attributeTypeRegistry,
        private ClassNameToTableName $classNameToTableName,
        private FilterValuePreparer $filterValuePreparer,
        private SqlPredicateBuilder $sqlPredicateBuilder,
    ) {
    }

    public function canOptimize(Node $node): bool
    {
        if ($node instanceof ConditionNode) {
            return true;
        }

        if (!$node instanceof GroupNode) {
            return false;
        }

        if (strtoupper($node->type) === 'OR') {
            return false;
        }

        foreach ($node->children as $child) {
            if (!$this->canOptimize($child)) {
                return false;
            }
        }

        return true;
    }

    public function build(Node $node): OptimizedFilterSource
    {
        $counter = 0;

        return $this->buildNodeSource($node, $counter);
    }

    private function buildNodeSource(Node $node, int &$counter): OptimizedFilterSource
    {
        if ($node instanceof ConditionNode) {
            return $this->buildConditionSource($node, $counter++);
        }

        if (!$node instanceof GroupNode) {
            throw new \RuntimeException(sprintf('Unsupported filter node "%s".', $node::class));
        }

        if (strtoupper($node->type) === 'OR') {
            throw new \RuntimeException('OR groups are not optimized in ids builder.');
        }

        return $this->buildAndSource($node, $counter);
    }

    private function buildAndSource(GroupNode $group, int &$counter): OptimizedFilterSource
    {
        $sources = [];

        foreach ($group->children as $child) {
            $sources[] = $this->buildNodeSource($child, $counter);
        }

        if ($sources === []) {
            return new OptimizedFilterSource('SELECT p.id AS product_id FROM product p', []);
        }

        $baseAlias = 'f0';
        $from = sprintf('(%s) %s', $sources[0]->sql, $baseAlias);
        $parameters = $sources[0]->parameters;
        $joins = [];

        for ($i = 1, $n = count($sources); $i < $n; $i++) {
            $alias = 'f' . $i;
            $joins[] = sprintf(
                'INNER JOIN (%s) %s ON %s.product_id = %s.product_id',
                $sources[$i]->sql,
                $alias,
                $alias,
                $baseAlias,
            );
            $parameters = array_merge($parameters, $sources[$i]->parameters);
        }

        $sql = sprintf(
            'SELECT DISTINCT %s.product_id FROM %s %s',
            $baseAlias,
            $from,
            implode(' ', $joins),
        );

        return new OptimizedFilterSource($sql, $parameters);
    }

    private function buildConditionSource(ConditionNode $condition, int $index): OptimizedFilterSource
    {
        $definition = $this->fieldResolver->resolve($condition->field);

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
                'i_' . $index,
            );

            return new OptimizedFilterSource(
                sprintf('SELECT p.id AS product_id FROM product p WHERE %s', $predicateSql),
                $parameters,
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
            'i_' . $index,
        );

        $parameters['i_' . $index . '_attr'] = $metadata->id;

        return new OptimizedFilterSource(
            sprintf(
                'SELECT v.product_id FROM %s v WHERE v.attribute_id = :%s AND %s',
                $tableName,
                'i_' . $index . '_attr',
                $predicateSql,
            ),
            $parameters,
        );
    }

    private function resolveTableNameByType(string $type): string
    {
        return $this->classNameToTableName->execute(
            $this->attributeTypeRegistry->getValueEntityClass($type),
        );
    }
}
