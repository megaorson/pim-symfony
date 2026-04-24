<?php
declare(strict_types=1);

namespace App\Service\Product\Flat;

use App\Entity\Product;

final readonly class ProductFlatAttributeValueExtractor
{
    /**
     * @return array<string, mixed>
     */
    public function extract(Product $product): array
    {
        $result = [];

        foreach ($product->getTextValues() as $value) {
            $attribute = $value->getAttribute();
            $result[$attribute->getCode()] = $value->getValue();
        }

        foreach ($product->getIntValues() as $value) {
            $attribute = $value->getAttribute();
            $result[$attribute->getCode()] = $value->getValue();
        }

        foreach ($product->getDecimalValues() as $value) {
            $attribute = $value->getAttribute();
            $result[$attribute->getCode()] = $value->getValue();
        }

        foreach ($product->getImageValues() as $value) {
            $attribute = $value->getAttribute();
            $result[$attribute->getCode()] = $value->getValue();
        }

        return $result;
    }
}
