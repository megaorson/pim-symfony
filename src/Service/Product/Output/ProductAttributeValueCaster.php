<?php
declare(strict_types=1);

namespace App\Service\Product\Output;

final class ProductAttributeValueCaster
{
    public function cast(mixed $value, ?string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int', 'integer' => (int) $value,
            'decimal', 'float' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            default => $value,
        };
    }
}
