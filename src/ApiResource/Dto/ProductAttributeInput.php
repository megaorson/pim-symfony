<?php
declare(strict_types=1);

namespace App\ApiResource\Dto;

final class ProductAttributeInput
{
    public string $code;
    public string $type;
    public string $name;
    public bool $isRequired = false;
    public bool $isFilterable = false;
    public bool $isSortable = false;
}
