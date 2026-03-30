<?php
declare(strict_types=1);

namespace App\Service\Product\Collection;

final readonly class ProductCollectionContextFactory
{
    public function create(array $filters = []): ProductCollectionContext
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = max(1, (int) ($filters['limit'] ?? 20));
        $offset = array_key_exists('offset', $filters)
            ? max(0, (int) $filters['offset'])
            : ($page - 1) * $limit;

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
        if (!is_string($value)) {
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
        if (!is_string($value)) {
            return [];
        }

        $result = [];

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

            $result[] = [
                'field' => $field,
                'direction' => $direction,
            ];
        }

        return $result;
    }
}
