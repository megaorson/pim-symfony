<?php
declare(strict_types=1);

namespace App\Service\Product\Flat;

use App\Service\Eav\Dto\AttributeMetadata;

final readonly class ProductFlatStructureBuilder
{
    public function __construct(
        private ProductFlatColumnNameResolver $columnNameResolver,
        private ProductFlatColumnTypeResolver $columnTypeResolver,
    ) {
    }

    /**
     * @param list<AttributeMetadata> $attributes
     * @return array{
     *   columns: array<string, string>,
     *   indexes: list<array{type: string, name: string, columns: list<string>}>
     * }
     */
    public function build(array $attributes): array
    {
        $columns = [
            'product_id' => 'INT UNSIGNED NOT NULL',
            'sku' => 'VARCHAR(255) NOT NULL',
            'created_at' => 'DATETIME NOT NULL',
            'updated_at' => 'DATETIME NOT NULL',
        ];

        $indexes = [
            ['type' => 'primary', 'name' => 'PRIMARY', 'columns' => ['product_id']],
            ['type' => 'unique', 'name' => 'uniq_sku', 'columns' => ['sku']],
            ['type' => 'index', 'name' => 'idx_updated_at', 'columns' => ['updated_at']],
            ['type' => 'index', 'name' => 'idx_created_at', 'columns' => ['created_at']],
        ];

        foreach ($attributes as $attribute) {
            if (!$attribute->filterable && !$attribute->sortable && !$attribute->selectable) {
                continue;
            }

            $columnName = $this->columnNameResolver->resolve($attribute->code);
            $columns[$columnName] = $this->columnTypeResolver->resolveSqlDefinition($attribute);
        }

        return [
            'columns' => $columns,
            'indexes' => $indexes,
        ];
    }
}
