<?php
declare(strict_types=1);

namespace App\Service\Product\Flat\Read;

use App\Service\Eav\AttributeMetadataProvider;
use App\Service\Eav\Dto\AttributeMetadata;
use App\Service\Product\Flat\ProductFlatColumnNameResolver;

final readonly class ProductFlatFieldMap
{
    public function __construct(
        private AttributeMetadataProvider $attributeMetadataProvider,
        private ProductFlatColumnNameResolver $columnNameResolver,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getSelectableFieldToColumnMap(): array
    {
        $map = [
            'id' => 'product_id',
            'sku' => 'sku',
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at',
        ];

        foreach ($this->attributeMetadataProvider->getAllSelectable() as $attribute) {
            $map[$attribute->code] = $this->columnNameResolver->resolve($attribute->code);
        }

        return $map;
    }

    /**
     * @return array<string, string>
     */
    public function getFilterableFieldToColumnMap(): array
    {
        $map = [
            'id' => 'product_id',
            'sku' => 'sku',
        ];

        foreach ($this->attributeMetadataProvider->getAllFilterable() as $attribute) {
            $map[$attribute->code] = $this->columnNameResolver->resolve($attribute->code);
        }

        return $map;
    }

    /**
     * @return array<string, string>
     */
    public function getSortableFieldToColumnMap(): array
    {
        $map = [
            'id' => 'product_id',
            'sku' => 'sku',
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at',
        ];

        foreach ($this->attributeMetadataProvider->getAllSortable() as $attribute) {
            $map[$attribute->code] = $this->columnNameResolver->resolve($attribute->code);
        }

        return $map;
    }

    /**
     * Общий map для filter compiler.
     *
     * @return array<string, string>
     */
    public function getFilterFieldToColumnMap(): array
    {
        return array_replace(
            $this->getSelectableFieldToColumnMap(),
            $this->getFilterableFieldToColumnMap(),
            $this->getSortableFieldToColumnMap(),
        );
    }

    /**
     * @return array<string, AttributeMetadata>
     */
    public function getAttributeMetadataMap(): array
    {
        $result = [];

        foreach ($this->attributeMetadataProvider->getAll() as $attribute) {
            $result[$attribute->code] = $attribute;
        }

        return $result;
    }

    public function getFieldType(string $field): string
    {
        return match ($field) {
            'id' => 'int',
            'sku' => 'text',
            'createdAt', 'updatedAt' => 'datetime',
            default => $this->getAttributeMetadataMap()[$field]->type ?? 'text',
        };
    }
}
