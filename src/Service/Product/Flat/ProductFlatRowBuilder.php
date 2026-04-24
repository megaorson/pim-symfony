<?php
declare(strict_types=1);

namespace App\Service\Product\Flat;

use App\Entity\Product;
use App\Service\Eav\Dto\AttributeMetadata;

final readonly class ProductFlatRowBuilder
{
    public function __construct(
        private ProductFlatColumnNameResolver $columnNameResolver,
    ) {
    }

    /**
     * @param array<string, AttributeMetadata> $attributesByCode
     * @param array<string, mixed> $attributeValuesByCode
     * @return array<string, mixed>
     */
    public function build(
        Product $product,
        array $attributesByCode,
        array $attributeValuesByCode,
    ): array {
        return array_merge(
            [
                'product_id' => $product->getId(),
                'sku' => $product->getSku(),
                'created_at' => $product->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $product->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ],
            $this->buildAttributeColumns($attributesByCode, $attributeValuesByCode),
        );
    }

    /**
     * @param array<string, AttributeMetadata> $attributesByCode
     * @param array<string, mixed> $attributeValuesByCode
     * @return array<string, mixed>
     */
    private function buildAttributeColumns(
        array $attributesByCode,
        array $attributeValuesByCode,
    ): array {
        $row = [];

        foreach ($attributesByCode as $code => $attribute) {
            $columnName = $this->columnNameResolver->resolve($code);
            $row[$columnName] = $attributeValuesByCode[$code] ?? null;
        }

        return $row;
    }
}
