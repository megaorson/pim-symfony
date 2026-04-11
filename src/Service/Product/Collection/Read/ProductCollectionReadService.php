<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read;

use App\ApiResource\Dto\ProductCollectionOutput;
use App\Service\Product\Collection\ProductCollectionContext;
use App\Service\Product\Collection\Read\Fetcher\ProductAttributeValuesFetcher;
use App\Service\Product\Collection\Read\Fetcher\ProductBaseFieldsFetcher;
use App\Service\Product\Collection\Read\Fetcher\ProductCountFetcher;
use App\Service\Product\Collection\Read\Fetcher\ProductIdsFetcher;

final readonly class ProductCollectionReadService
{
    public function __construct(
        private ProductCollectionQueryPlanner $queryPlanner,
        private ProductIdsFetcher $idsFetcher,
        private ProductCountFetcher $countFetcher,
        private ProductBaseFieldsFetcher $baseFieldsFetcher,
        private ProductAttributeValuesFetcher $attributeValuesFetcher,
        private ProductCollectionAssembler $assembler,
    ) {
    }

    public function getCollection(ProductCollectionContext $context): ProductCollectionOutput
    {
        $plan = $this->queryPlanner->build($context);

        $total = $this->countFetcher->count($plan);

        if ($total === 0) {
            return $this->assembler->assembleEmpty($context, $total);
        }

        $ids = $this->idsFetcher->fetchIds($plan);

        if ($ids === []) {
            return $this->assembler->assembleEmpty($context, $total);
        }

        $baseRows = $this->baseFieldsFetcher->fetchByIds($ids, $plan);
        $attributeRowsByProductId = $this->attributeValuesFetcher->fetchByIds($ids, $plan);

        return $this->assembler->assemble(
            ids: $ids,
            baseRows: $baseRows,
            attributeRowsByProductId: $attributeRowsByProductId,
            context: $context,
            total: $total,
        );
    }
}
