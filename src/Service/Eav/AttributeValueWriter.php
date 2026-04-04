<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Entity\Product;
use App\Entity\ProductAttribute;

final readonly class AttributeValueWriter
{
    public function __construct(
        private AttributeValueHandlerRegistry $attributeValueHandlerRegistry,
    ) {
    }

    public function write(Product $product, ProductAttribute $attribute, mixed $rawValue): void
    {
        if ($rawValue === null) {
            throw new \LogicException(sprintf(
                'Cannot write null value for attribute "%s".',
                $attribute->getCode()
            ));
        }

        if ($product->hasAttributeValue($attribute->getCode())) {
            throw new \LogicException(sprintf(
                'Attribute value for code "%s" already exists.',
                $attribute->getCode()
            ));
        }

        $handler = $this->attributeValueHandlerRegistry->get(
            $attribute->getType(),
            $attribute->getCode()
        );

        $valueEntity = $handler->create($product, $attribute, $rawValue);

        $product->addAttributeValueObject($valueEntity);
    }
}
