<?php
declare(strict_types=1);

namespace App\Service\Product\Flat;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\Eav\AttributeMetadataProvider;
use Doctrine\DBAL\Connection;

final readonly class ProductFlatUpdater implements ProductFlatUpdaterInterface
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductFlatTableRegistry $tableRegistry,
        private AttributeMetadataProvider $attributeMetadataProvider,
        private ProductFlatAttributeValueExtractor $attributeValueExtractor,
        private ProductFlatRowBuilder $rowBuilder,
        private ProductFlatWriter $writer,
        private Connection $connection,
    ) {
    }

    public function updateProduct(int $productId): void
    {
        $product = $this->productRepository->find($productId);

        if (!$product instanceof Product) {
            return;
        }

        $attributesByCode = [];

        foreach ($this->attributeMetadataProvider->getAll() as $attribute) {
            if (!$attribute->filterable && !$attribute->sortable && !$attribute->selectable) {
                continue;
            }

            $attributesByCode[$attribute->code] = $attribute;
        }

        $attributeValuesByCode = $this->attributeValueExtractor->extract($product);

        $row = $this->rowBuilder->build(
            product: $product,
            attributesByCode: $attributesByCode,
            attributeValuesByCode: $attributeValuesByCode,
        );

        $this->writer->upsertRows(
            $this->tableRegistry->getActiveTable(),
            [$row]
        );
    }

    public function deleteProduct(int $productId): void
    {
        $this->connection->executeStatement(
            sprintf('DELETE FROM %s WHERE product_id = :id', $this->tableRegistry->getActiveTable()),
            ['id' => $productId]
        );
    }
}
