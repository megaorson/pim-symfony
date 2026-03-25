<?php
declare(strict_types=1);

namespace App\DTO;

class SkuAttributeValueDTO
{
    public ?ProductAttribute $attribute = null;

    public ?string $valueText = null;
    public ?int $valueInt = null;
    public ?float $valueDecimal = null;
    public ?string $valueImage = null;
}
