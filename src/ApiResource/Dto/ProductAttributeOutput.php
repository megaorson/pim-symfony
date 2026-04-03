<?php
declare(strict_types=1);

namespace App\ApiResource\Dto;

final class ProductAttributeOutput
{
    public int $id;
    public string $code;
    public string $type;
    public string $name;
    public bool $isRequired;
    public bool $isFilterable;
    public bool $isSortable;
    public \DateTimeImmutable $createdAt;
    public \DateTimeImmutable $updatedAt;
}
