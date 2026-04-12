<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Fetcher;

use App\Service\Product\Collection\Read\Filter\FilterCountCompilerFacade;
use App\Service\Product\Collection\Read\ProductCollectionQueryPlan;
use Doctrine\DBAL\Connection;

final readonly class ProductCountFetcher
{
    public function __construct(
        private Connection $connection,
        private FilterCountCompilerFacade $filterCountCompilerFacade,
    ) {
    }

    public function count(ProductCollectionQueryPlan $plan): int
    {
        $compiled = $this->filterCountCompilerFacade->compile($plan);

        return (int) $this->connection
            ->executeQuery($compiled->sql, $compiled->parameters)
            ->fetchOne();
    }
}
