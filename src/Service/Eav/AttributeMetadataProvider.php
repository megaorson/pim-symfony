<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Repository\ProductAttributeRepository;
use App\Service\Eav\Dto\AttributeMetadata;

final class AttributeMetadataProvider
{
    public function __construct(
        private readonly ProductAttributeRepository $attributeRepository
    ) {
    }

    /**
     * @param list<string> $codes
     * @return array<string, AttributeMetadata>
     */
    public function getByCodes(array $codes): array
    {
        $codes = array_values(array_unique(array_filter($codes)));

        if ($codes === []) {
            return [];
        }

        $rows = $this->attributeRepository->createQueryBuilder('a')
            ->select('a.id, a.code, a.type')
            ->andWhere('a.code IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getArrayResult();

        $result = [];

        foreach ($rows as $row) {
            $result[(string) $row['code']] = new AttributeMetadata(
                id: (int) $row['id'],
                code: (string) $row['code'],
                type: (string) $row['type'],
                filterable: true,
                selectable: true,
                sortable: true
            );
        }

        return $result;
    }

    /**
     * @return list<AttributeMetadata>
     */
    public function getAllFilterable(): array
    {
        $rows = $this->attributeRepository->createQueryBuilder('a')
            ->select('a.id, a.code, a.type')
            ->orderBy('a.code', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn(array $row): AttributeMetadata => new AttributeMetadata(
                id: (int) $row['id'],
                code: (string) $row['code'],
                type: (string) $row['type'],
                filterable: true,
                selectable: true,
                sortable: true
            ),
            $rows
        );
    }

    /**
     * @return list<AttributeMetadata>
     */
    public function getAllSelectable(): array
    {
        return $this->getAllFilterable();
    }
}
