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
        return $type === 'text';
    }

    protected function getFormType()
    : string
    {
        return TextareaType::class;
    }

    protected function createEntity()
    {
        return new ProductAttributeValueText();
    }

    protected function getCollection(Product $product)
    {
        return $product->getTextValues();
    }

    protected function normalizeValue($value)
    {
        return (string)$value;
    }
}
