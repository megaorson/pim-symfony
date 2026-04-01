<?php
declare(strict_types=1);

namespace App\Service\Product\Collection;

use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductCollectionContextFactory
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function createFromFilters(array $filters): ProductCollectionContext
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = max(1, min(100, (int) ($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        return new ProductCollectionContext(
            filter: $this->normalizeNullableString($filters['filter'] ?? null),
            selectedFields: $this->parseCommaSeparatedList($filters['select'] ?? null),
            sorts: $this->parseSorts($filters['sort'] ?? null),
            limit: $limit,
            offset: $offset,
            page: $page,
        );
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @return list<string>
     */
    private function parseCommaSeparatedList(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $result = [];

        foreach (explode(',', $value) as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            $result[] = $item;
        }

        return array_values(array_unique($result));
    }

    /**
     * @return list<array{field: string, direction: 'ASC'|'DESC'}>
     */
    private function parseSorts(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $result = [];
        $seen = [];

        foreach (explode(',', $value) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $direction = 'ASC';
            $field = $part;

            if (str_starts_with($part, '-')) {
                $direction = 'DESC';
                $field = substr($part, 1);
            }

            $field = trim($field);
            if ($field === '') {
                continue;
            }

            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $field)) {
                throw new \InvalidArgumentException($this->translator->trans('product.collection.invalid_sort_field', ['%field%' => $field]));
            }

            if (isset($seen[$field])) {
                continue;
            }

            $seen[$field] = true;

            $result[] = [
                'field' => $field,
                'direction' => $direction,
            ];
        }

        return $result;
    }
}
