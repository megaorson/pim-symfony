<?php
declare(strict_types=1);

namespace App\Service\Product\Field;

use App\Service\Eav\AttributeMetadataProvider;
use App\Service\Eav\Dto\AttributeMetadata;

final readonly class ProductCollectionFieldProvider
{
    public function __construct(
        private ProductSystemFieldRegistry $systemFieldRegistry,
        private AttributeMetadataProvider $attributeMetadataProvider,
    ) {
    }

    public function isKnownField(string $field): bool
    {
        if ($this->systemFieldRegistry->isSystemField($field)) {
            return true;
        }

        return $this->attributeMetadataProvider->getByCode($field) instanceof AttributeMetadata;
    }

    public function isSelectable(string $field): bool
    {
        if ($this->systemFieldRegistry->isSystemField($field)) {
            return $this->systemFieldRegistry->isSelectable($field);
        }

        $metadata = $this->attributeMetadataProvider->getByCode($field);

        return $metadata instanceof AttributeMetadata && $metadata->selectable;
    }

    public function isSortable(string $field): bool
    {
        if ($this->systemFieldRegistry->isSystemField($field)) {
            return $this->systemFieldRegistry->isSortable($field);
        }

        $metadata = $this->attributeMetadataProvider->getByCode($field);

        return $metadata instanceof AttributeMetadata && $metadata->sortable;
    }

    public function isFilterable(string $field): bool
    {
        if ($this->systemFieldRegistry->isSystemField($field)) {
            return $this->systemFieldRegistry->isFilterable($field);
        }

        $metadata = $this->attributeMetadataProvider->getByCode($field);

        return $metadata instanceof AttributeMetadata && $metadata->filterable;
    }

    /**
     * @return list<string>
     */
    public function getSelectableFields(): array
    {
        $fields = array_merge(
            $this->systemFieldRegistry->getSelectableFields(),
            $this->getAttributeCodesByCapability('selectable')
        );

        return $this->normalizeFields($fields);
    }

    /**
     * @return list<string>
     */
    public function getSortableFields(): array
    {
        $fields = array_merge(
            $this->systemFieldRegistry->getSortableFields(),
            $this->getAttributeCodesByCapability('sortable')
        );

        return $this->normalizeFields($fields);
    }

    /**
     * @return list<string>
     */
    public function getFilterableFields(): array
    {
        $fields = array_merge(
            $this->systemFieldRegistry->getFilterableFields(),
            $this->getAttributeCodesByCapability('filterable')
        );

        return $this->normalizeFields($fields);
    }

    /**
     * @param list<string> $fields
     * @return list<string>
     */
    private function normalizeFields(array $fields): array
    {
        $fields = array_values(array_unique($fields));
        sort($fields);

        return $fields;
    }

    /**
     * @param 'selectable'|'sortable'|'filterable' $capability
     * @return list<string>
     */
    private function getAttributeCodesByCapability(string $capability): array
    {
        $result = [];

        foreach ($this->attributeMetadataProvider->getAll() as $metadata) {
            if (!$this->matchesCapability($metadata, $capability)) {
                continue;
            }

            $result[] = $metadata->code;
        }

        return $result;
    }

    /**
     * @param 'selectable'|'sortable'|'filterable' $capability
     */
    private function matchesCapability(AttributeMetadata $metadata, string $capability): bool
    {
        return match ($capability) {
            'selectable' => $metadata->selectable,
            'sortable' => $metadata->sortable,
            'filterable' => $metadata->filterable,
        };
    }
}
