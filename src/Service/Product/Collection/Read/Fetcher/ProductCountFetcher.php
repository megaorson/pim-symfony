<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Fetcher;

use App\Service\Product\Collection\Read\Filter\FilterCompilerFacade;
use App\Service\Product\Collection\Read\ProductCollectionQueryPlan;
use Doctrine\DBAL\Connection;

final readonly class ProductCountFetcher
{
    public function __construct(
        private Connection $connection,
        private FilterCompilerFacade $filterCompilerFacade,
    ) {
    }

    public function count(ProductCollectionQueryPlan $plan): int
    {
        $compiledFilter = $this->filterCompilerFacade->compile($plan);

        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('COUNT(*)')
            ->from('product', 'p');

        if (!$compiledFilter->isEmpty()) {
            $qb->where($compiledFilter->sql);

            foreach ($compiledFilter->parameters as $name => $value) {
                $qb->setParameter($name, $value);
            }
        }

        return (int) $qb->executeQuery()->fetchOne();
    }
}
