<?php
declare(strict_types=1);

namespace App\Service\Eav\AttributeValueHandler;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeTypeInterface;

interface AttributeValueHandlerInterface
{
    public function supports(string $attributeType): bool;

    public function create(Product $product, ProductAttribute $attribute, mixed $rawValue): ProductAttributeTypeInterface;

    public function update(ProductAttributeTypeInterface $valueEntity, ProductAttribute $attribute, mixed $rawValue): void;

    public function getAttributeType(): string;
}
