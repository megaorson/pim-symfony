<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Service\Eav\Dto\AttributeMetadata;

final class FilterFieldProvider
{
    public function __construct(
        private readonly AttributeMetadataProvider $metadataProvider
    ) {
    }

    /**
     * @return list<string>
     */
    public function getFilterableCodes(): array
    {
        return array_map(
            static fn(AttributeMetadata $attribute): string => $attribute->code,
            $this->metadataProvider->getAllFilterable()
        );
    }

    /**
     * @return list<string>
     */
    public function getSelectableCodes(): array
    {
        return array_map(
            static fn(AttributeMetadata $attribute): string => $attribute->code,
            $this->metadataProvider->getAllSelectable()
        );
    }
}
