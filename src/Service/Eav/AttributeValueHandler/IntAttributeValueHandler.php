<?php
declare(strict_types=1);

namespace App\Service\Eav\AttributeValueHandler;

use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeValueInt;

final class IntAttributeValueHandler extends AbstractAttributeValueHandler
{
    public function getAttributeType(): string
    {
        return ProductAttributeValueInt::TYPE;
    }

    protected function normalize(ProductAttribute $attribute, mixed $rawValue): int
    {
        if (is_int($rawValue)) {
            return $rawValue;
        }

        if (is_string($rawValue) && preg_match('/^-?\d+$/', $rawValue) === 1) {
            return (int) $rawValue;
        }

        throw $this->createInvalidValueException(
            $attribute->getCode(),
            'product.attribute.invalid.expected_int'
        );
    }
}
