<?php
declare(strict_types=1);

namespace App\Form\Attribute\Handler;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Entity\Product;
use App\Entity\ProductAttributeValueText;

class TextAttributeFormHandler extends AbstractAttributeFormHandler
{
    public function supports(string $type)
    : bool {
        return $type === ProductAttributeValueText::TYPE;
    }

    protected function getFormType()
    : string
    {
        return TextareaType::class;
    }

    protected function createEntity()
    {
        return $this->attributeFactory->create(ProductAttributeValueText::TYPE);
    }

    protected function getCollection(Product $product)
    {
        return $product->getTextValues();
    }

    protected function normalizeValue($value, $existing = null, Product $product = null)
    {
        return (string)$value;
    }
}
