<?php
declare(strict_types=1);

namespace App\Form\Attribute\Handler;

use Symfony\Component\Form\Extension\Core\Type\FileType;
use App\Entity\Product;
use App\Entity\ProductAttributeValueImage;

class ImageAttributeFormHandler extends AbstractAttributeFormHandler
{
    public function supports(string $type)
    : bool {
        return $type === 'image';
    }

    protected function getFormType()
    : string
    {
        return FileType::class;
    }

    protected function createEntity()
    {
        return new ProductAttributeValueImage();
    }

    protected function getCollection(Product $product)
    {
        return $product->getImageValues();
    }

    protected function normalizeValue($value)
    {
        return $value;
    }
}
