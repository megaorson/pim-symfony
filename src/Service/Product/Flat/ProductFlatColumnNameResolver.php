<?php
declare(strict_types=1);

namespace App\Service\Product\Flat;

final readonly class ProductFlatColumnNameResolver
{
    public function resolve(string $attributeCode): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9_]+/', '_', $attributeCode) ?? $attributeCode;
        $normalized = strtolower(trim($normalized, '_'));

        if ($normalized === '') {
            throw new \InvalidArgumentException('Attribute code cannot be resolved to an empty flat column name.');
        }

        return 'attr_' . $normalized;
    }
}
