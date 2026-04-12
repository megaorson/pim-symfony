<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

final readonly class BaseFieldTypeResolver
{
    public function resolve(string $field): string
    {
        return match ($field) {
            'id' => 'int',
            default => 'string',
        };
    }
}
