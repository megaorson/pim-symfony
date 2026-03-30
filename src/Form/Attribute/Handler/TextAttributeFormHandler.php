<?php
declare(strict_types=1);

namespace App\Form\Attribute\Handler;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Entity\Product;
use App\Entity\ProductAttributeValueText;

class TextAttributeFormHandler extends AbstractAttributeFormHandler
{
    protected function getFormType(): string
    {
        return TextareaType::class;
    }

    protected function getCollection(Product $product)
    {
        return $product->getTextValues();
    }

    protected function normalizeValue($value, $existing = null, Product $product = null)
    {
        return (string)$value;
    }

    protected function getAttributeType(): string
    {
        return ProductAttributeValueText::TYPE;
    }
}
