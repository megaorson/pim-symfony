<?php
declare(strict_types=1);

namespace App\Form\Attribute\Handler;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use App\Entity\Product;
use App\Entity\ProductAttributeValueInt;

class IntAttributeFormHandler extends AbstractAttributeFormHandler
{
    public function supports(string $type)
    : bool {
        return $type === 'int';
    }

    protected function getFormType()
    : string
    {
        return IntegerType::class;
    }

    protected function createEntity()
    {
        return new ProductAttributeValueInt();
    }

    protected function getCollection(Product $product)
    {
        return $product->getIntValues();
    }

    protected function normalizeValue($value)
    {
        return (int)$value;
    }
}
