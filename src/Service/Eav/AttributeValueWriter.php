<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use Doctrine\ORM\EntityManagerInterface;

final class AttributeValueWriter
{
    public function __construct(
        private readonly AttributeValueHandlerRegistry $attributeValueHandlerRegistry,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function write(Product $product, ProductAttribute $attribute, mixed $rawValue): void
    {
        $handler = $this->attributeValueHandlerRegistry->get(
            $attribute->getType(),
            $attribute->getCode()
        );

        $valueEntity = $handler->create($product, $attribute, $rawValue);

        $this->em->persist($valueEntity);
    }
}
