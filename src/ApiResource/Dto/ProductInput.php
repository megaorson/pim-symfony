<?php
declare(strict_types=1);

namespace App\ApiResource\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ProductInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $sku = '';

    /**
     * @var array<string, mixed>
     */
    #[Assert\Type('array')]
    public array $attributes = [];
}
