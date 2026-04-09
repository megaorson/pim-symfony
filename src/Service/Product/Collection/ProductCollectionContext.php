<?php
declare(strict_types=1);

namespace App\Service\Product\Collection;

final class ProductCollectionContext
{
    /**
     * @param list<string> $selectedFields
     * @param list<array{field: string, direction: 'ASC'|'DESC'}> $sorts
     */
    public function __construct(
        public readonly ?string $filter,
        public readonly array $selectedFields,
        public readonly array $sorts,
        public readonly int $limit,
        public readonly int $offset,
        public readonly int $page,
    ) {
    }

    public function shouldReturnAllFields(): bool
    {
        return $this->selectedFields === [] || in_array('*', $this->selectedFields, true);
    }

    public function isFieldSelected(string $field): bool
    {
        return $this->shouldReturnAllFields() || in_array($field, $this->selectedFields, true);
    }
}
