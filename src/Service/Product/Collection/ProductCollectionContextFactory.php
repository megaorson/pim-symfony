<?php
declare(strict_types=1);

namespace App\Service\Product\Collection;

use App\Exception\Api\InvalidPaginationException;
use App\Exception\Api\InvalidSelectException;
use App\Exception\Api\InvalidSortException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductCollectionContextFactory
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT = 100;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function createFromFilters(array $filters): ProductCollectionContext
    {
        $page = $this->parsePage($filters['page'] ?? null);
        $limit = $this->parseLimit($filters['limit'] ?? null);
        $offset = ($page - 1) * $limit;

        return new ProductCollectionContext(
            filter: $this->normalizeNullableString($filters['filter'] ?? null),
            selectedFields: $this->parseSelectFields($filters['select'] ?? null),
            sorts: $this->parseSorts($filters['sort'] ?? null),
            limit: $limit,
            offset: $offset,
            page: $page,
        );
    }

    private function parseNumber(
        mixed $value,
        int $default = self::DEFAULT_LIMIT,
        string $type = 'limit'
    ): int {
        if ($value === null || $value === '') {
            return $default;
        }

        if (!is_scalar($value)) {
            throw new InvalidPaginationException(
                $this->translator->trans('product.collection.invalid_' . $type, [
                    '%value%' => get_debug_type($value),
                ])
            );
        }

        $value = trim((string) $value);

        if ($value === '' || preg_match('/^\d+$/', $value) !== 1) {
            throw new InvalidPaginationException(
                $this->translator->trans('product.collection.invalid_' . $type, [
                    '%value%' => $value,
                ])
            );
        }

        return (int) $value;
    }

    private function parsePage(mixed $value): int
    {
        $page = $this->parseNumber($value, self::DEFAULT_PAGE, 'page');

        if ($page < 1) {
            throw new InvalidPaginationException(
                $this->translator->trans('product.collection.invalid_page', [
                    '%value%' => (string) $page,
                ])
            );
        }

        return $page;
    }

    private function parseLimit(mixed $value): int
    {
        $limit = $this->parseNumber($value);

        if ($limit < 1 || $limit > self::MAX_LIMIT) {
            throw new InvalidPaginationException(
                $this->translator->trans('product.collection.invalid_limit', [
                    '%value%' => (string) $limit,
                    '%min%' => '1',
                    '%max%' => (string) self::MAX_LIMIT,
                ])
            );
        }

        return $limit;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function parseString(
        mixed $value,
        string $classException = 'InvalidSelectException',
        string $type = 'sort_field',
        callable $normalize = null
    ): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $result = [];
        $seen = [];

        foreach (explode(',', $value) as $part) {
            $part = trim($part);

            if ($part === '') {
                throw new $classException(
                    $this->translator->trans('product.collection.invalid_' . $type, [
                        '%field%' => $part,
                    ])
                );
            }
            $item = $normalize($part);
            $field = $part;

            if (isset($seen[$field])) {
                continue;
            }

            $seen[$field] = true;
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private function parseSelectFields(mixed $value): array
    {
        return $this->parseString(
            $value,
            'InvalidSelectException',
            'select_field',
            function ($item) {
                if ($item === '') {
                    throw new InvalidSelectException(
                        $this->translator->trans('product.collection.invalid_select_field', [
                            '%field%' => $item,
                        ])
                    );
                }

                if ($item !== '*' && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $item) !== 1) {
                    throw new InvalidSelectException(
                        $this->translator->trans('product.collection.invalid_select_field', [
                            '%field%' => $item,
                        ])
                    );
                }

                return $item;
            }
        );
    }

    /**
     * @return list<array{field: string, direction: 'ASC'|'DESC'}>
     */
    private function parseSorts(mixed $value): array
    {
        return $this->parseString(
            $value,
            'InvalidSortException',
            'sort_field',
            function ($part) {
                $direction = 'ASC';
                $field = $part;

                if (str_starts_with($part, '-')) {
                    $direction = 'DESC';
                    $field = substr($part, 1);
                }

                $field = trim($field);

                if ($field === '' || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $field) !== 1) {
                    throw new InvalidSortException(
                        $this->translator->trans('product.collection.invalid_sort_field', [
                            '%field%' => $field !== '' ? $field : $part,
                        ])
                    );
                }

                return [
                    'field' => $field,
                    'direction' => $direction,
                ];
            }
        );
    }
}
