<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read;

use App\ApiResource\Dto\ProductCollectionOutput;
use App\ApiResource\Dto\ProductOutput;
use App\Service\Product\Collection\ProductCollectionContext;

final readonly class ProductCollectionAssembler
{
    public function assembleEmpty(ProductCollectionContext $context, int $total = 0): ProductCollectionOutput
    {
        return new ProductCollectionOutput(
            items: [],
            total: $total,
            limit: $context->limit,
            offset: $context->offset,
        );
    }

    /**
     * @param list<int> $ids
     * @param array<int, array<string, mixed>> $baseRows
     * @param array<int, array<string, mixed>> $attributeRowsByProductId
     */
    public function assemble(
        array $ids,
        array $baseRows,
        array $attributeRowsByProductId,
        ProductCollectionContext $context,
        int $total,
    ): ProductCollectionOutput {
        $items = [];

        foreach ($ids as $id) {
            $base = $baseRows[$id] ?? ['id' => $id];
            $attributes = $attributeRowsByProductId[$id] ?? [];

            $baseAttributes = $this->normalizeBaseAttributes($base);

            $items[] = new ProductOutput(
                id: $id,
                attributes: array_merge($baseAttributes, $attributes),
            );
        }

        return new ProductCollectionOutput(
            items: $items,
            total: $total,
            limit: $context->limit,
            offset: $context->offset,
        );
    }

    /**
     * @param array<string, mixed> $base
     * @return array<string, mixed>
     */
    private function normalizeBaseAttributes(array $base): array
    {
        unset($base['id']);

        return $base;
    }
}
