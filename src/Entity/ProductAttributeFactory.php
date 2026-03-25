<?php
declare(strict_types=1);

namespace App\Entity;

class ProductAttributeFactory
{
    public function __construct(
        protected array $attributes = []
    ) {
    }

    public function create(string $attributeType)
    : ProductAttributeTypeInterface {
        return new $this->attributes[$attributeType]();
    }

    public function getAttributes()
    : array
    {
        return $this->attributes;
    }
}
