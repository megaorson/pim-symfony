<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Fetcher;

use App\Service\Product\Collection\Read\Filter\FilterCompilerFacade;
use App\Service\Product\Collection\Read\ProductCollectionQueryPlan;
use App\Service\Product\Collection\Read\Sort\SortSqlBuilder;
use Doctrine\DBAL\Connection;

final readonly class ProductIdsFetcher
{
    public function __construct(
        private Connection $connection,
        private SortSqlBuilder $sortSqlBuilder,
        private FilterCompilerFacade $filterCompilerFacade,
    ) {
    }

    /**
     * @return list<int>
     */
    public function fetchIds(ProductCollectionQueryPlan $plan): array
    {
        $compiledFilter = $this->filterCompilerFacade->compile($plan);

        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('p.id')
            ->from('product', 'p')
            ->setFirstResult($plan->offset)
            ->setMaxResults($plan->limit);

        if (!$compiledFilter->isEmpty()) {
            $qb->where($compiledFilter->sql);

            foreach ($compiledFilter->parameters as $name => $value) {
                $qb->setParameter($name, $value);
            }
        }

        $this->sortSqlBuilder->apply($qb, $plan);

        $rows = $qb->executeQuery()->fetchFirstColumn();

        return array_map(static fn (mixed $id): int => (int) $id, $rows);
    }
}
