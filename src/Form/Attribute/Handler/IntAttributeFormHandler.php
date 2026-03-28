<?php
declare(strict_types=1);

namespace App\Form\Attribute\Handler;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use App\Entity\Product;
use App\Entity\ProductAttributeValueInt;

class IntAttributeFormHandler extends AbstractAttributeFormHandler
{
    protected function getFormType()
    : string
    {
        return IntegerType::class;
    }

    protected function getCollection(Product $product)
    {
        return $product->getIntValues();
    }

    protected function normalizeValue($value, $existing = null, Product $product = null)
    {
        return (int)$value;
    }

    protected function getAttributeType()
    : string
    {
        return ProductAttributeValueInt::TYPE;
    }
}
