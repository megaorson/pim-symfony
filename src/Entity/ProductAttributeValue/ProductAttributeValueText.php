<?php
declare(strict_types=1);

namespace App\Entity\ProductAttributeValue;

use App\Entity\AbstractProductAttributeValue;

#[ORM\Entity]
class ProductAttributeValueText extends AbstractProductAttributeValue
{
    #[ORM\Column(type: 'text')]
    private string $value;
}
