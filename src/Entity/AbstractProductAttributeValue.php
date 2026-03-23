<?php
declare(strict_types=1);

namespace App\Entity;

#[ORM\MappedSuperclass]
abstract class AbstractProductAttributeValue
{
    #[ORM\ManyToOne(targetEntity: Sku::class, inversedBy: '...')]
    protected Sku $sku;

    #[ORM\ManyToOne(targetEntity: ProductAttribute::class)]
    protected ProductAttribute $attribute;
}
