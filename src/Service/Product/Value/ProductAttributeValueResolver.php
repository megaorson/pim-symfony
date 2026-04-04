<?php
declare(strict_types=1);

namespace App\Service\Product\Value;

use App\Entity\Product;
use App\Entity\ProductAttributeTypeInterface;

final readonly class ProductAttributeValueResolver
{
    public function getValueByCode(Product $product, string $code): mixed
    {
        return $product->getAttributeValue($code);
    }

    public function hasValueByCode(Product $product, string $code): bool
    {
        return $product->hasAttributeValue($code);
    }

    public function getValueObjectByCode(Product $product, string $code): ?ProductAttributeTypeInterface
    {
        return $product->getAttributeValueObject($code);
    }
}
