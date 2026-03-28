<?php
declare(strict_types=1);

namespace App\Entity;

interface ProductAttributeTypeInterface
{
    public function getType()
    : string;

    public function getValue()
    : mixed;

    public function setValue($value)
    : static;

    public function getAttribute()
    : ?ProductAttribute;

    public function setAttribute(?ProductAttribute $productAttribute)
    : static;

    public function getProduct()
    : ?Product;

    public function setProduct(?Product $product)
    : static;
}
