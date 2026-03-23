<?php

namespace App\Entity\ProductAttributeValue;

use App\Entity\AbstractProductAttributeValue;

#[ORM\Entity]
class ProductAttributeValueDecimal extends AbstractProductAttributeValue
{
    #[ORM\Column(type: 'float')]
    private float $value;
}
