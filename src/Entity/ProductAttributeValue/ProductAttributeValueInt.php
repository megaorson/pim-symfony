<?php
declare(strict_types=1);

namespace App\Entity\ProductAttributeValue;

use App\Entity\AbstractProductAttributeValue;

#[ORM\Entity]
class ProductAttributeValueInt extends AbstractProductAttributeValue
{
    #[ORM\Column(type: 'integer')]
    private int $value;
}
