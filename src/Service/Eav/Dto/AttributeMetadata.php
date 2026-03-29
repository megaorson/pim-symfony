<?php
declare(strict_types=1);

namespace App\Service\Eav\Dto;

final class AttributeMetadata
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $type,
        public readonly bool $filterable = true,
        public readonly bool $selectable = true
    ) {
    }
}
