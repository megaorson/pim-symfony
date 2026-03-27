<?php
declare(strict_types=1);

namespace App\DTO;

class ProductInput
{
    public string $sku;

    /** @var array<string, mixed> */
    public array $attributes = [];
}
