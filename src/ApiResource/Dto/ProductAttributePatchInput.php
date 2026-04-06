<?php
declare(strict_types=1);

namespace App\ApiResource\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ProductAttributePatchInput
{
    #[Assert\NotBlank]
    public ?string $name = null;

    public ?bool $isRequired = null;
    public ?bool $isFilterable = null;
    public ?bool $isSortable = null;
    public ?bool $isSelectable = null;
}
