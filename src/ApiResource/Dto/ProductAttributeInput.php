<?php
declare(strict_types=1);

namespace App\ApiResource\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ProductAttributeInput
{
    #[Assert\NotBlank]
    public ?string $code = null;

    #[Assert\NotBlank]
    public ?string $type = null;

    #[Assert\NotBlank]
    public ?string $name = null;

    public bool $isRequired = false;
    public bool $isFilterable = false;
    public bool $isSortable = false;
    public bool $isSelectable = true;
}
