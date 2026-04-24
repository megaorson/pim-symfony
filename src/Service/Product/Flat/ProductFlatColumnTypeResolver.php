<?php
declare(strict_types=1);

namespace App\Service\Product\Flat;

use App\Service\Eav\Dto\AttributeMetadata;

final readonly class ProductFlatColumnTypeResolver
{
    public function resolveSqlDefinition(AttributeMetadata $attribute): string
    {
        return match ($attribute->type) {
            'text' => 'VARCHAR(255) DEFAULT NULL',
            'int' => 'INT DEFAULT NULL',
            'decimal' => 'DECIMAL(12,4) DEFAULT NULL',
            'image' => 'VARCHAR(512) DEFAULT NULL',
            default => throw new \RuntimeException(sprintf(
                'Unsupported flat column type "%s" for attribute "%s".',
                $attribute->type,
                $attribute->code
            )),
        };
    }
}
