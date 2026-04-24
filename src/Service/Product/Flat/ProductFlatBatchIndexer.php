<?php
declare(strict_types=1);

namespace App\Service\Product\Flat;

use App\Repository\ProductRepository;
use App\Service\Eav\AttributeMetadataProvider;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProductFlatBatchIndexer
{
    public function __construct(
        private ProductRepository $productRepository,
        private AttributeMetadataProvider $attributeMetadataProvider,
        private ProductFlatAttributeValueExtractor $attributeValueExtractor,
        private ProductFlatRowBuilder $rowBuilder,
        private ProductFlatWriter $writer,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array{
     *   columns: array<string, string>,
     *   indexes: list<array{type: string, name: string, columns: list<string>}>
     * } $structure
     */
    public function reindexIntoTable(
        string $tableName,
        array $structure,
        ?int $limit,
        int $batchSize,
        ?callable $onProgress = null,
    ): void {
        $offset = 0;
        $processed = 0;

        $attributesByCode = $this->getFlatAttributesByCode();

        while (true) {
            $products = $this->productRepository->findBatchForFlatIndex(
                limit: $batchSize,
                offset: $offset,
                maxTotal: $limit,
            );

            if ($products === []) {
                break;
            }

            $rows = [];

            foreach ($products as $product) {
                $attributeValuesByCode = $this->attributeValueExtractor->extract($product);

                $rows[] = $this->rowBuilder->build(
                    product: $product,
                    attributesByCode: $attributesByCode,
                    attributeValuesByCode: $attributeValuesByCode,
                );

                $processed++;
            }

            $this->writer->upsertRows($tableName, $rows);

            if ($onProgress !== null) {
                $onProgress(count($rows));
            }

            $offset += count($products);

            $this->entityManager->clear();

            if ($limit !== null && $processed >= $limit) {
                break;
            }
        }
    }

    /**
     * @return array<string, \App\Service\Eav\Dto\AttributeMetadata>
     */
    private function getFlatAttributesByCode(): array
    {
        $result = [];

        foreach ($this->attributeMetadataProvider->getAll() as $attribute) {
            if (!$attribute->filterable && !$attribute->sortable && !$attribute->selectable) {
                continue;
            }

            $result[$attribute->code] = $attribute;
        }

        return $result;
    }
}
