<?php
declare(strict_types=1);

namespace App\Service\Product\Collection;

use App\ApiResource\Dto\ProductCollectionOutput;
use App\Entity\Product;
use App\Service\Product\Factory\ProductOutputFactory;

final readonly class ProductCollectionResultMapper
{
    public function __construct(
        private ProductOutputFactory $productOutputFactory,
    ) {
    }

    /**
     * @param list<Product> $products
     */
    public function mapCollection(array $products, ProductCollectionContext $context, int $total): ProductCollectionOutput
    {
        $items = array_map(
            fn (Product $product) => $this->productOutputFactory->create($product, $context),
            $products,
        );

        return new ProductCollectionOutput(
            items: $items,
            total: $total,
            limit: $context->limit,
            offset: $context->offset,
        );
    }
}
