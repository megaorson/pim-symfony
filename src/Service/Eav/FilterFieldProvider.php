<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Service\Product\Field\ProductCollectionFieldProvider;

final readonly class FilterFieldProvider
{
    public function __construct(
        private ProductCollectionFieldProvider $fieldProvider,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getFilterableCodes(): array
    {
        return $this->fieldProvider->getFilterableFields();
    }

    /**
     * @return list<string>
     */
    public function getSelectableCodes(): array
    {
        return $this->fieldProvider->getSelectableFields();
    }

    /**
     * @return list<string>
     */
    public function getSortableCodes(): array
    {
        return $this->fieldProvider->getSortableFields();
    }

    public function isFilterable(string $field): bool
    {
        return $this->fieldProvider->isFilterable($field);
    }

    public function isSelectable(string $field): bool
    {
        return $this->fieldProvider->isSelectable($field);
    }

    public function isSortable(string $field): bool
    {
        return $this->fieldProvider->isSortable($field);
    }

    public function isKnownField(string $field): bool
    {
        return $this->fieldProvider->isKnownField($field);
    }
}
