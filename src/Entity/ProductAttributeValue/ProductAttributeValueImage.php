<?php

namespace App\Entity\ProductAttributeValue;

use App\Entity\AbstractProductAttributeValue;

#[ORM\Entity]
class ProductAttributeValueImage extends AbstractProductAttributeValue
{
    #[ORM\Column(type: 'string')]
    private string $path;
}
