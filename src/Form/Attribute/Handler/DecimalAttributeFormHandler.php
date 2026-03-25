<?php
declare(strict_types=1);

namespace App\Form\Attribute\Handler;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use App\Entity\Product;
use App\Entity\ProductAttributeValueDecimal;

class DecimalAttributeFormHandler extends AbstractAttributeFormHandler
{
    public function supports(string $type)
    : bool {
        return $type === 'decimal';
    }

    protected function getFormType()
    : string
    {
        return NumberType::class;
    }

    protected function createEntity()
    {
        return new ProductAttributeValueDecimal();
    }

    protected function getCollection(Product $product)
    {
        return $product->getDecimalValues();
    }

    protected function normalizeValue($value)
    {
        return $value !== null ? (float)$value : null;
    }
}
