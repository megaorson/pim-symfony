<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Repository\ProductAttributeRepository;
use App\Service\Eav\Dto\AttributeMetadata;

final readonly class AttributeMetadataProvider
{
    public function __construct(
        private ProductAttributeRepository $attributeRepository,
    ) {
    }

    public function getByCode(string $code): ?AttributeMetadata
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $row = $this->attributeRepository
            ->createQueryBuilder('a')
            ->select('a.id, a.code, a.type')
            ->andWhere('a.code = :code')
            ->setParameter('code', $code)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!is_array($row)) {
            return null;
        }

        return $this->mapRowToMetadata($row);
    }

    /**
     * @param list<string> $codes
     * @return array<string, AttributeMetadata>
     */
    public function getByCodes(array $codes): array
    {
        $codes = array_values(array_unique(array_filter(
            array_map(static fn (mixed $code): string => trim((string) $code), $codes),
            static fn (string $code): bool => $code !== ''
        )));

        if ($codes === []) {
            return [];
        }

        $rows = $this->attributeRepository
            ->createQueryBuilder('a')
            ->select('a.id, a.code, a.type')
            ->andWhere('a.code IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getArrayResult();

        $result = [];

        foreach ($rows as $row) {
            $metadata = $this->mapRowToMetadata($row);
            $result[$metadata->code] = $metadata;
        }

        return $result;
    }

    /**
     * @return list<AttributeMetadata>
     */
    public function getAll(): array
    {
        $rows = $this->attributeRepository
            ->createQueryBuilder('a')
            ->select('a.id, a.code, a.type')
            ->orderBy('a.code', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            fn (array $row): AttributeMetadata => $this->mapRowToMetadata($row),
            $rows
        );
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
     * @param array{id: mixed, code: mixed, type: mixed} $row
     */
    private function mapRowToMetadata(array $row): AttributeMetadata
    {
        return new AttributeMetadata(
            id: (int) $row['id'],
            code: (string) $row['code'],
            type: (string) $row['type'],
            filterable: true,
            selectable: true,
            sortable: true,
        );
    }
}
