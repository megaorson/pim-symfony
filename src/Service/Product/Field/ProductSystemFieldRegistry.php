<?php
declare(strict_types=1);

namespace App\Service\Product\Field;

use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductSystemFieldRegistry
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    /**
     * @var array<string, array{
     *     doctrineField: string,
     *     selectable: bool,
     *     sortable: bool,
     *     filterable: bool
     * }>
     */
    private const FIELDS = [
        'id' => [
            'doctrineField' => 'id',
            'selectable' => true,
            'sortable' => true,
            'filterable' => true,
        ],
        'sku' => [
            'doctrineField' => 'sku',
            'selectable' => true,
            'sortable' => true,
            'filterable' => true,
        ],
        'createdAt' => [
            'doctrineField' => 'createdAt',
            'selectable' => true,
            'sortable' => true,
            'filterable' => false,
        ],
        'updatedAt' => [
            'doctrineField' => 'updatedAt',
            'selectable' => true,
            'sortable' => true,
            'filterable' => false,
        ],
    ];

    public function isSystemField(string $field): bool
    {
        return isset(self::FIELDS[$field]);
    }

    public function getDoctrineField(string $field): string
    {
        if (!$this->isSystemField($field)) {
            throw new \InvalidArgumentException($this->translator->trans('product.field.unknown', ['%field%' => $field]));
        }

        return self::FIELDS[$field]['doctrineField'];
    }

    public function isSelectable(string $field): bool
    {
        return $this->isSystemField($field) && self::FIELDS[$field]['selectable'];
    }

    public function isSortable(string $field): bool
    {
        return $this->isSystemField($field) && self::FIELDS[$field]['sortable'];
    }

    public function isFilterable(string $field): bool
    {
        return $this->isSystemField($field) && self::FIELDS[$field]['filterable'];
    }

    /**
     * @return list<string>
     */
    public function getSelectableFields(): array
    {
        return array_values(array_keys(array_filter(
            self::FIELDS,
            static fn (array $config): bool => $config['selectable'] === true
        )));
    }

    /**
     * @return list<string>
     */
    public function getSortableFields(): array
    {
        return array_values(array_keys(array_filter(
            self::FIELDS,
            static fn (array $config): bool => $config['sortable'] === true
        )));
    }

    /**
     * @return list<string>
     */
    public function getFilterableFields(): array
    {
        return array_values(array_keys(array_filter(
            self::FIELDS,
            static fn (array $config): bool => $config['filterable'] === true
        )));
    }
}
