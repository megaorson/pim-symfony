<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Eav\Dto\AttributeMetadata;

final readonly class FilterFieldDefinition
{
    public function __construct(
        public string $field,
        public bool $isSystemField,
        public ?string $systemColumn = null,
        public ?AttributeMetadata $attributeMetadata = null,
    ) {
    }
}
