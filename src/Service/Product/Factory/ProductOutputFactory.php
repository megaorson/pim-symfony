<?php
declare(strict_types=1);

namespace App\Service\Product\Factory;

use App\ApiResource\Dto\ProductOutput;
use App\Entity\Product;
use App\Service\Product\Collection\ProductCollectionContext;

final class ProductOutputFactory
{
    public function create(Product $product, ProductCollectionContext $context): ProductOutput
    {
        $attributes = [];

        foreach ($product->getAllAttributeValues() as $value) {
            $attribute = $value->getAttribute();
            $code = $attribute?->getCode();

            if ($code === null) {
                continue;
            }

            if (!$context->shouldReturnAllFields() && !$context->isFieldSelected($code)) {
                continue;
            }

            $attributes[$code] = $value->getValue();
        }

        if ($context->isFieldSelected('sku')) {
            $attributes['sku'] = $product->getSku();
        }

        if ($context->isFieldSelected('createdAt') && method_exists($product, 'getCreatedAt')) {
            $attributes['createdAt'] = $product->getCreatedAt()?->format(DATE_ATOM);
        }

        if ($context->isFieldSelected('updatedAt') && method_exists($product, 'getUpdatedAt')) {
            $attributes['updatedAt'] = $product->getUpdatedAt()?->format(DATE_ATOM);
        }

        return new ProductOutput(
            id: (int) $product->getId(),
            attributes: $attributes,
        );
    }
}
