<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Eav\AttributeTypeRegistry;
use App\Service\Eav\Dto\AttributeMetadata;
use App\Service\Eav\Filter\Ast\ConditionNode;
use App\Service\ProductAttributeValue\ClassNameToTableName;

final readonly class FilterConditionSourceFactory
{
    public function __construct(
        private FilterFieldResolver $fieldResolver,
        private AttributeTypeRegistry $attributeTypeRegistry,
        private ClassNameToTableName $classNameToTableName,
        private FilterValuePreparer $filterValuePreparer,
        private SqlPredicateBuilder $sqlPredicateBuilder,
        private BaseFieldTypeResolver $baseFieldTypeResolver,
    ) {
    }

    public function createExistsCondition(ConditionNode $condition, int $index): CompiledFilter
    {
        $definition = $this->fieldResolver->resolve($condition->field);
        $paramBase = 'f_' . $index;

        if ($definition->isSystemField) {
            $preparedValue = $this->filterValuePreparer->prepare(
                $condition->field,
                $condition->operator,
                $condition->value,
                $this->filterValuePreparer->resolveBaseFieldType($condition->field)
            );

            [$sql, $parameters] = $this->sqlPredicateBuilder->build(
                'p.' . ($definition->systemColumn ?? $condition->field),
                $condition->operator,
                $preparedValue,
                $paramBase,
            );

            return new CompiledFilter($sql, $parameters);
        }

        $metadata = $this->requireAttributeMetadata($definition, $condition->field);
        $preparedValue = $this->filterValuePreparer->prepare(
            $condition->field,
            $condition->operator,
            $condition->value,
            $metadata->type,
        );

        [$valueSql, $parameters] = $this->sqlPredicateBuilder->build('v.value', $condition->operator, $preparedValue, $paramBase);
        $parameters[$paramBase . '_attr'] = $metadata->id;

        return new CompiledFilter(sprintf(
            'EXISTS (SELECT 1 FROM %s v WHERE v.product_id = p.id AND v.attribute_id = :%s AND %s)',
            $this->resolveTableNameByType($metadata->type),
            $paramBase . '_attr',
            $valueSql,
        ), $parameters);
    }

    public function createProductIdSource(ConditionNode $condition, string $paramBase): FilterConditionSource
    {
        $definition = $this->fieldResolver->resolve($condition->field);
        $projectedColumn = $this->makeProjectedColumnName($condition->field);

        if ($definition->isSystemField) {
            $column = $definition->systemColumn ?? $condition->field;
            $preparedValue = $this->filterValuePreparer->prepare(
                $condition->field,
                $condition->operator,
                $condition->value,
                $this->filterValuePreparer->resolveBaseFieldType($condition->field)
            );

            [$predicateSql, $parameters] = $this->sqlPredicateBuilder->build('p.' . $column, $condition->operator, $preparedValue, $paramBase);

            return new FilterConditionSource(
                sprintf('SELECT p.id AS product_id, p.%s AS %s FROM product p WHERE %s', $column, $projectedColumn, $predicateSql),
                $parameters,
                $projectedColumn,
            );
        }

        $metadata = $this->requireAttributeMetadata($definition, $condition->field);
        $preparedValue = $this->filterValuePreparer->prepare(
            $condition->field,
            $condition->operator,
            $condition->value,
            $metadata->type,
        );

        [$predicateSql, $parameters] = $this->sqlPredicateBuilder->build('v.value', $condition->operator, $preparedValue, $paramBase);
        $parameters[$paramBase . '_attr'] = $metadata->id;

        return new FilterConditionSource(
            sprintf(
                'SELECT v.product_id, v.value AS %s FROM %s v WHERE v.attribute_id = :%s AND %s',
                $projectedColumn,
                $this->resolveTableNameByType($metadata->type),
                $paramBase . '_attr',
                $predicateSql,
            ),
            $parameters,
            $projectedColumn,
        );
    }

    public function makeProjectedColumnName(string $field): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]+/', '_', $field) ?? $field;
        return 'sort_' . strtolower($safe);
    }

    private function requireAttributeMetadata(FilterFieldDefinition $definition, string $field): AttributeMetadata
    {
        $metadata = $definition->attributeMetadata;
        if (!$metadata instanceof AttributeMetadata) {
            throw new \RuntimeException(sprintf('Attribute metadata is required for field "%s".', $field));
        }
        return $metadata;
    }

    private function resolveTableNameByType(string $type): string
    {
        return $this->classNameToTableName->execute(
            $this->attributeTypeRegistry->getValueEntityClass($type)
        );
    }

    public function createForCount(ConditionNode $condition, string $paramBase): FilterConditionSource
    {
        $definition = $this->fieldResolver->resolve($condition->field);

        if ($definition->isSystemField) {
            $column = $definition->systemColumn ?? $condition->field;
            $type = $this->baseFieldTypeResolver->resolve($condition->field);

            $preparedValue = $this->filterValuePreparer->prepare(
                field: $condition->field,
                operator: $condition->operator,
                rawValue: $condition->value,
                type: $type,
            );

            [$predicateSql, $parameters] = $this->sqlPredicateBuilder->build(
                fieldExpression: 'p.' . $column,
                operator: $condition->operator,
                preparedValue: $preparedValue,
                paramBase: $paramBase,
            );

            return new FilterConditionSource(
                sql: sprintf(
                    'SELECT p.id AS product_id FROM product p WHERE %s',
                    $predicateSql
                ),
                parameters: $parameters,
            );
        }

        $metadata = $definition->attributeMetadata;
        if ($metadata === null) {
            throw new \RuntimeException(sprintf('Attribute metadata is required for field "%s".', $condition->field));
        }

        $tableName = $this->classNameToTableName->execute(
            $this->attributeTypeRegistry->getValueEntityClass($metadata->type)
        );

        $preparedValue = $this->filterValuePreparer->prepare(
            field: $condition->field,
            operator: $condition->operator,
            rawValue: $condition->value,
            type: $metadata->type,
        );

        [$predicateSql, $parameters] = $this->sqlPredicateBuilder->build(
            fieldExpression: 'v.value',
            operator: $condition->operator,
            preparedValue: $preparedValue,
            paramBase: $paramBase,
        );

        $parameters[$paramBase . '_attr'] = $metadata->id;

        return new FilterConditionSource(
            sql: sprintf(
                'SELECT v.product_id
             FROM %s v
             WHERE v.attribute_id = :%s
               AND %s',
                $tableName,
                $paramBase . '_attr',
                $predicateSql
            ),
            parameters: $parameters,
        );
    }
}
