<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read;

use App\ApiResource\Dto\ProductCollectionOutput;
use App\Service\Product\Collection\ProductCollectionContext;
use App\Service\Product\Flat\Read\ProductFlatCountFetcher;
use App\Service\Product\Flat\Read\ProductFlatDataFetcher;
use App\Service\Product\Flat\Read\ProductFlatOutputFactory;
use App\Service\Product\Flat\Read\ProductFlatQueryPlanner;

final readonly class ProductCollectionReadService
{
    public function __construct(
        private ProductFlatQueryPlanner $queryPlanner,
        private ProductFlatCountFetcher $countFetcher,
        private ProductFlatDataFetcher $dataFetcher,
        private ProductFlatOutputFactory $outputFactory,
    ) {
    }

    public function getCollection(ProductCollectionContext $context): ProductCollectionOutput
    {
        $plan = $this->queryPlanner->build($context);

        $total = $this->countFetcher->count($plan);
        $rows = $this->dataFetcher->fetch($plan);

        return $this->outputFactory->createCollection(
            rows: $rows,
            total: $total,
            limit: $plan->limit,
            offset: $plan->offset,
        );
    }
}
