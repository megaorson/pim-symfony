<?php
declare(strict_types=1);

namespace App\Service\Eav\AttributeValueHandler;

use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeValueText;

final class TextAttributeValueHandler extends AbstractAttributeValueHandler
{
    public function getAttributeType(): string
    {
        return ProductAttributeValueText::TYPE;
    }

    protected function normalize(ProductAttribute $attribute, mixed $rawValue): string
    {
        if (!is_string($rawValue)) {
            throw $this->createInvalidValueException(
                $attribute->getCode(),
                'product.attribute.invalid.expected_string'
            );
        }

        $value = trim($rawValue);

        if ($value === '') {
            throw $this->createInvalidValueException(
                $attribute->getCode(),
                'product.attribute.invalid.empty_string'
            );
        }

        return $value;
    }
}
