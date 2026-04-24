<?php
declare(strict_types=1);

namespace App\Service\Product\Flat\Read;

use App\ApiResource\Dto\ProductCollectionOutput;
use App\ApiResource\Dto\ProductOutput;
use App\Service\Eav\AttributeMetadataProvider;
use App\Service\Product\Output\ProductAttributeValueCaster;

final readonly class ProductFlatOutputFactory
{
    public function __construct(
        private ProductAttributeValueCaster $valueCaster,
        private AttributeMetadataProvider $attributeMetadataProvider,
    ) {}

    /**
     * @param list<array<string, mixed>> $rows
     * @return ProductOutput[]
     */
    public function createItems(array $rows): array
    {
        $attributesByCode = [];

        foreach ($this->attributeMetadataProvider->getAll() as $metadata) {
            $attributesByCode[$metadata->code] = $metadata;
        }

        $items = [];

        foreach ($rows as $row) {
            $id = isset($row['id']) ? (int) $row['id'] : 0;

            $attributes = [];

            foreach ($row as $fieldCode => $value) {
                if ($fieldCode === 'id') {
                    continue;
                }

                $metadata = $attributesByCode[$fieldCode] ?? null;
                $value = $this->valueCaster->cast(
                    value: $value,
                    type: $metadata?->type,
                );
                if (is_null($value)) {
                    continue;
                }

                $attributes[$fieldCode] = $value;
            }

            $items[] = new ProductOutput(
                id: $id,
                attributes: $attributes,
            );
        }

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function createCollection(
        array $rows,
        int $total,
        int $limit,
        int $offset,
    ): ProductCollectionOutput {
        return new ProductCollectionOutput(
            items: $this->createItems($rows),
            total: $total,
            limit: $limit,
            offset: $offset,
        );
    }
}
