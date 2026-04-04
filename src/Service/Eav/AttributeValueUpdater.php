<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Entity\Product;
use App\Entity\ProductAttribute;

final readonly class AttributeValueUpdater
{
    public function __construct(
        private AttributeValueWriter $attributeValueWriter,
    ) {
    }

    public function upsert(Product $product, ProductAttribute $attribute, mixed $value): void
    {
        $existingValue = $product->getAttributeValueObject($attribute->getCode());

        if ($existingValue === null) {
            if ($value === null) {
                return;
            }

            $this->attributeValueWriter->write($product, $attribute, $value);

            return;
        }

        if ($value === null) {
            $product->removeAttributeValueObject($existingValue);

            return;
        }

        $existingValue->setValue($value);
    }
}
