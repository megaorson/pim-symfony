<?php
declare(strict_types=1);

namespace App\Service\Product\Flat\Read;

use App\Service\Product\Flat\ProductFlatTableRegistry;
use Doctrine\DBAL\Connection;

final readonly class ProductFlatCountFetcher
{
    public function __construct(
        private Connection $connection,
        private ProductFlatTableRegistry $tableRegistry,
        private ProductFlatFilterSqlCompiler $filterSqlCompiler,
    ) {
    }

    public function count(ProductFlatQueryPlan $plan): int
    {
        $tableName = $this->tableRegistry->getActiveTable();
        $compiledFilter = $this->filterSqlCompiler->compile($plan->rawFilter, 'pf');

        $sql = sprintf(
            'SELECT COUNT(*) FROM %s pf WHERE %s',
            $tableName,
            $compiledFilter->sql,
        );

        return (int) $this->connection
            ->executeQuery($sql, $compiledFilter->parameters)
            ->fetchOne();
    }
}
