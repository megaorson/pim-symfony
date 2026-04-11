<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Fetcher;

use App\Service\Eav\AttributeTypeRegistry;
use App\Service\Eav\Dto\AttributeMetadata;
use App\Service\Product\Collection\Read\ProductAttributeSelectionResolver;
use App\Service\Product\Collection\Read\ProductCollectionQueryPlan;
use App\Service\ProductAttributeValue\ClassNameToTableName;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class ProductAttributeValuesFetcher
{
    public function __construct(
        private Connection $connection,
        private ProductAttributeSelectionResolver $attributeSelectionResolver,
        private AttributeTypeRegistry $attributeTypeRegistry,
        private ClassNameToTableName $classNameToTableName,
    ) {
    }

    /**
     * @param list<int> $ids
     * @return array<int, array<string, mixed>>
     */
    public function fetchByIds(array $ids, ProductCollectionQueryPlan $plan): array
    {
        if ($ids === []) {
            return [];
        }

        $selectedAttributesByCode = $this->attributeSelectionResolver->resolveSelectedAttributes(
            $plan->shouldLoadAllAttributes(),
            $plan->selectedAttributesByCode,
        );

        if ($selectedAttributesByCode === []) {
            return [];
        }

        $groupedByType = $this->groupAttributesByType($selectedAttributesByCode);
        $result = [];

        foreach ($groupedByType as $type => $attributesByCode) {
            $tableName = $this->resolveTableNameByType($type);
            $rows = $this->fetchRowsForType($tableName, $ids, $attributesByCode);

            foreach ($rows as $row) {
                $productId = (int) $row['product_id'];
                $attributeId = (int) $row['attribute_id'];
                $value = $row['value'];

                $metadata = $this->findMetadataById($attributesByCode, $attributeId);

                if ($metadata === null) {
                    continue;
                }

                $result[$productId][$metadata->code] = $this->normalizeValue($value, $metadata->type);
            }
        }

        return $result;
    }

    /**
     * @param array<string, AttributeMetadata> $selectedAttributesByCode
     * @return array<string, array<string, AttributeMetadata>>
     */
    private function groupAttributesByType(array $selectedAttributesByCode): array
    {
        $result = [];

        foreach ($selectedAttributesByCode as $code => $metadata) {
            $result[$metadata->type][$code] = $metadata;
        }

        return $result;
    }

    /**
     * @param array<string, AttributeMetadata> $attributesByCode
     * @return list<array{product_id: mixed, attribute_id: mixed, value: mixed}>
     */
    private function fetchRowsForType(string $tableName, array $ids, array $attributesByCode): array
    {
        $attributeIds = array_map(
            static fn (AttributeMetadata $metadata): int => $metadata->id,
            array_values($attributesByCode)
        );

        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('v.product_id', 'v.attribute_id', 'v.value')
            ->from($tableName, 'v')
            ->where('v.product_id IN (:ids)')
            ->andWhere('v.attribute_id IN (:attributeIds)')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER)
            ->setParameter('attributeIds', $attributeIds, ArrayParameterType::INTEGER);

        /** @var list<array{product_id: mixed, attribute_id: mixed, value: mixed}> $rows */
        $rows = $qb->executeQuery()->fetchAllAssociative();

        return $rows;
    }

    /**
     * @param array<string, AttributeMetadata> $attributesByCode
     */
    private function findMetadataById(array $attributesByCode, int $attributeId): ?AttributeMetadata
    {
        foreach ($attributesByCode as $metadata) {
            if ($metadata->id === $attributeId) {
                return $metadata;
            }
        }

        return null;
    }

    private function resolveTableNameByType(string $type): string
    {
        return $this->classNameToTableName->execute(
            $this->attributeTypeRegistry->getValueEntityClass($type)
        );
    }

    private function normalizeValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int' => $value !== null ? (int) $value : null,
            'decimal' => $value !== null ? (float) $value : null,
            default => $value,
        };
    }
}
