<?php
declare(strict_types=1);

namespace App\Service\Eav\AttributeValueHandler;

use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeValueDecimal;

final class DecimalAttributeValueHandler extends AbstractAttributeValueHandler
{
    public function getAttributeType(): string
    {
        return ProductAttributeValueDecimal::TYPE;
    }

    protected function normalize(ProductAttribute $attribute, mixed $rawValue): float
    {
        if (is_int($rawValue) || is_float($rawValue)) {
            return (float) $rawValue;
        }

        if (is_string($rawValue) && is_numeric($rawValue)) {
            return (float) $rawValue;
        }

        throw $this->createInvalidValueException(
            $attribute->getCode(),
            'product.attribute.invalid.expected_decimal'
        );
    }
}
