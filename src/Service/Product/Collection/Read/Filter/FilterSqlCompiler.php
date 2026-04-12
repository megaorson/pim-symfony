<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Eav\AttributeTypeRegistry;
use App\Service\Eav\Dto\AttributeMetadata;
use App\Service\Eav\Filter\Ast\ConditionNode;
use App\Service\Eav\Filter\Ast\GroupNode;
use App\Service\Eav\Filter\Ast\Node;
use App\Service\ProductAttributeValue\ClassNameToTableName;

final readonly class FilterSqlCompiler
{
    public function __construct(
        private FilterFieldResolver $fieldResolver,
        private AttributeTypeRegistry $attributeTypeRegistry,
        private ClassNameToTableName $classNameToTableName,
        private FilterValuePreparer $filterValuePreparer,
        private SqlPredicateBuilder $sqlPredicateBuilder,
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
        $preparedValue = $this->filterValuePreparer->prepare($field, $operator, $value, $metadata->type);

        [$valueSql, $parameters] = $this->sqlPredicateBuilder->build(
            fieldExpression: 'v.value',
            operator: $operator,
            preparedValue: $preparedValue,
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
            $valueSql,
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
        int $index,
    ): CompiledFilter {
        $paramBase = 'f_' . $index;
        $preparedValue = $this->filterValuePreparer->prepare(
            $field,
            $operator,
            $value,
            $this->filterValuePreparer->resolveBaseFieldType($field),
        );

        [$sql, $parameters] = $this->sqlPredicateBuilder->build(
            fieldExpression: 'p.' . $column,
            operator: $operator,
            preparedValue: $preparedValue,
            paramBase: $paramBase,
        );

        return new CompiledFilter($sql, $parameters);
    }

    private function resolveTableNameByType(string $type): string
    {
        return $this->classNameToTableName->execute(
            $this->attributeTypeRegistry->getValueEntityClass($type),
        );
    }
}
