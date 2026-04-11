<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read;

use App\Service\Eav\AttributeMetadataProvider;
use App\Service\Eav\Dto\AttributeMetadata;

final readonly class ProductAttributeSelectionResolver
{
    public function __construct(
        private AttributeMetadataProvider $attributeMetadataProvider,
    ) {
    }

    /**
     * @param array<string, AttributeMetadata> $explicitlySelectedAttributesByCode
     * @return array<string, AttributeMetadata>
     */
    public function resolveSelectedAttributes(
        bool $selectAllFields,
        array $explicitlySelectedAttributesByCode,
    ): array {
        if (!$selectAllFields) {
            return $explicitlySelectedAttributesByCode;
        }

        return $this->getAllSelectableAttributes();
    }

    /**
     * @return array<string, AttributeMetadata>
     */
    private function getAllSelectableAttributes(): array
    {
        $all = $this->attributeMetadataProvider->getAll();

        $result = [];

        foreach ($all as $metadata) {
            if (!$metadata instanceof AttributeMetadata) {
                continue;
            }

            if (!$metadata->selectable) {
                continue;
            }

            $result[$metadata->code] = $metadata;
        }

        return $result;
    }
}
