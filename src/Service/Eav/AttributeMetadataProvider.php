<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Repository\ProductAttributeRepository;
use App\Service\Eav\Dto\AttributeMetadata;

final class AttributeMetadataProvider
{
    /**
     * @var array<string, AttributeMetadata|null>
     */
    private array $attributeCache = [];

    /**
     * @var list<AttributeMetadata>|null
     */
    private ?array $allAttributesCache = null;

    public function __construct(
        private readonly ProductAttributeRepository $attributeRepository,
    ) {
    }

    public function getByCode(string $code): ?AttributeMetadata
    {
        $code = $this->normalizeCode($code);

        if ($code === null) {
            return null;
        }

        if (array_key_exists($code, $this->attributeCache)) {
            return $this->attributeCache[$code];
        }

        $row = $this->createBaseQueryBuilder()
            ->andWhere('a.code = :code')
            ->setParameter('code', $code)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!is_array($row)) {
            $this->attributeCache[$code] = null;

            return null;
        }

        $metadata = $this->mapRowToMetadata($row);
        $this->attributeCache[$metadata->code] = $metadata;

        return $metadata;
    }

    /**
     * @param list<string> $codes
     * @return array<string, AttributeMetadata>
     */
    public function getByCodes(array $codes): array
    {
        $normalizedCodes = $this->normalizeCodes($codes);

        if ($normalizedCodes === []) {
            return [];
        }

        $result = [];
        $missingCodes = [];

        foreach ($normalizedCodes as $code) {
            if (array_key_exists($code, $this->attributeCache)) {
                $cached = $this->attributeCache[$code];

                if ($cached !== null) {
                    $result[$code] = $cached;
                }

                continue;
            }

            $missingCodes[] = $code;
        }

        if ($missingCodes !== []) {
            $rows = $this->createBaseQueryBuilder()
                ->andWhere('a.code IN (:codes)')
                ->setParameter('codes', $missingCodes)
                ->getQuery()
                ->getArrayResult();

            $loadedCodes = [];

            foreach ($rows as $row) {
                $metadata = $this->mapRowToMetadata($row);
                $this->attributeCache[$metadata->code] = $metadata;
                $result[$metadata->code] = $metadata;
                $loadedCodes[$metadata->code] = true;
            }

            foreach ($missingCodes as $code) {
                if (!isset($loadedCodes[$code])) {
                    $this->attributeCache[$code] = null;
                }
            }
        }

        return $result;
    }

    /**
     * @return list<AttributeMetadata>
     */
    public function getAll(): array
    {
        if ($this->allAttributesCache !== null) {
            return $this->allAttributesCache;
        }

        $rows = $this->createBaseQueryBuilder()
            ->orderBy('a.code', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $result = array_map(
            fn (array $row): AttributeMetadata => $this->mapRowToMetadata($row),
            $rows
        );

        foreach ($result as $metadata) {
            $this->attributeCache[$metadata->code] = $metadata;
        }

        $this->allAttributesCache = array_values($result);

        return $this->allAttributesCache;
    }

    /**
     * @return list<AttributeMetadata>
     */
    public function getAllFilterable(): array
    {
        return array_values(array_filter(
            $this->getAll(),
            static fn (AttributeMetadata $metadata): bool => $metadata->filterable
        ));
    }

    /**
     * @return list<AttributeMetadata>
     */
    public function getAllSelectable(): array
    {
        return array_values(array_filter(
            $this->getAll(),
            static fn (AttributeMetadata $metadata): bool => $metadata->selectable
        ));
    }

    /**
     * @return list<AttributeMetadata>
     */
    public function getAllSortable(): array
    {
        return array_values(array_filter(
            $this->getAll(),
            static fn (AttributeMetadata $metadata): bool => $metadata->sortable
        ));
    }

    /**
     * @return list<AttributeMetadata>
     */
    public function getAllRequired(): array
    {
        return array_values(array_filter(
            $this->getAll(),
            static fn (AttributeMetadata $metadata): bool => $metadata->required
        ));
    }

    private function createBaseQueryBuilder()
    {
        return $this->attributeRepository
            ->createQueryBuilder('a')
            ->select('a.id, a.code, a.type, a.isSelectable, a.isFilterable, a.isSortable, a.isRequired');
    }

    private function normalizeCode(string $code): ?string
    {
        $code = trim($code);

        return $code === '' ? null : $code;
    }

    /**
     * @param list<string> $codes
     * @return list<string>
     */
    private function normalizeCodes(array $codes): array
    {
        return array_values(array_unique(array_filter(
            array_map(
                fn (mixed $code): string => trim((string) $code),
                $codes
            ),
            static fn (string $code): bool => $code !== ''
        )));
    }

    /**
     * @param array{
     *     id: mixed,
     *     code: mixed,
     *     type: mixed,
     *     isSelectable: mixed,
     *     isFilterable: mixed,
     *     isSortable: mixed,
     *     isRequired: mixed
     * } $row
     */
    private function mapRowToMetadata(array $row): AttributeMetadata
    {
        return new AttributeMetadata(
            id: (int) $row['id'],
            code: (string) $row['code'],
            type: (string) $row['type'],
            filterable: (bool) $row['isFilterable'],
            selectable: (bool) $row['isSelectable'],
            sortable: (bool) $row['isSortable'],
            required: (bool) $row['isRequired'],
        );
    }
}
