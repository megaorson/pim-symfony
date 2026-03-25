<?php
declare(strict_types=1);

namespace App\Form\Attribute\Handler;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use Symfony\Component\Form\FormInterface;

interface AttributeFormHandlerInterface
{
    public function supports(string $type)
    : bool;

    public function buildField(FormInterface $builder, ProductAttribute $attribute, ?Product $product)
    : void;

    public function handleSubmit(FormInterface $builder, ProductAttribute $attribute, Product $product)
    : void;
}
