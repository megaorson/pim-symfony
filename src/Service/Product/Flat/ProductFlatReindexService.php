<?php
declare(strict_types=1);

namespace App\Service\Product\Flat;

use App\Service\Eav\AttributeMetadataProvider;
use Doctrine\DBAL\Connection;

final readonly class ProductFlatReindexService
{
    public function __construct(
        private ProductFlatTableRegistry $tableRegistry,
        private AttributeMetadataProvider $attributeMetadataProvider,
        private ProductFlatStructureBuilder $structureBuilder,
        private ProductFlatSchemaManager $schemaManager,
        private ProductFlatBatchIndexer $batchIndexer,
        private Connection $connection,
    ) {
    }

    public function countProducts(?int $limit = null): int
    {
        $total = (int) $this->connection
            ->executeQuery('SELECT COUNT(*) FROM product')
            ->fetchOne();

        if ($limit !== null) {
            return min($limit, $total);
        }

        return $total;
    }

    public function rebuild(?int $limit = null, int $batchSize = 500, ?callable $onProgress = null): void
    {
        $buildVersion = 'build_' . (new \DateTimeImmutable())->format('Ymd_His');
        $targetTable = $this->tableRegistry->getStandbyTable();

        $this->tableRegistry->markBuilding($buildVersion);

        try {
            $attributes = $this->attributeMetadataProvider->getAll();
            $structure = $this->structureBuilder->build($attributes);

            $this->schemaManager->rebuildTable($targetTable, $structure);

            $this->batchIndexer->reindexIntoTable(
                tableName: $targetTable,
                structure: $structure,
                limit: $limit,
                batchSize: $batchSize,
                onProgress: $onProgress,
            );

            $this->tableRegistry->switchActiveTable($targetTable, $buildVersion);
        } catch (\Throwable $e) {
            $this->tableRegistry->markFailed($buildVersion);
            throw $e;
        }
    }
}
