<?php
declare(strict_types=1);

namespace App\ApiResource\Dto;

final class ProductAttributePatchInput
{
    public ?string $name = null;
    public ?bool $isRequired = null;
    public ?bool $isFilterable = null;
    public ?bool $isSortable = null;
    public ?bool $isSelectable = null;
}
