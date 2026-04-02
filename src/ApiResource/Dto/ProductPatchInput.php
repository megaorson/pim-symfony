<?php
declare(strict_types=1);

namespace App\ApiResource\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ProductPatchInput
{
    #[Assert\Length(max: 100)]
    public ?string $sku = null;

    /**
     * @var array<string, mixed>
     */
    #[Assert\Type('array')]
    public array $attributes = [];
}
