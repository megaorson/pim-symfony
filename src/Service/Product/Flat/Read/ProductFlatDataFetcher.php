<?php
declare(strict_types=1);

namespace App\Service\Product\Flat\Read;

use App\Service\Product\Flat\ProductFlatTableRegistry;
use Doctrine\DBAL\Connection;

final readonly class ProductFlatDataFetcher
{
    public function __construct(
        private Connection $connection,
        private ProductFlatTableRegistry $tableRegistry,
        private ProductFlatFieldMap $fieldMap,
        private ProductFlatFilterSqlCompiler $filterSqlCompiler,
        private ProductFlatSortSqlBuilder $sortSqlBuilder,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetch(ProductFlatQueryPlan $plan): array
    {
        $tableName = $this->tableRegistry->getActiveTable();
        $selectableMap = $this->fieldMap->getSelectableFieldToColumnMap();
        $compiledFilter = $this->filterSqlCompiler->compile($plan->rawFilter, 'pf');

        $qb = $this->connection->createQueryBuilder();
        $qb->from($tableName, 'pf');

        $fields = $plan->selectedFields;

        if (!in_array('id', $fields, true)) {
            array_unshift($fields, 'id');
        }

        foreach ($fields as $field) {
            $column = $selectableMap[$field] ?? null;

            if ($column === null) {
                continue;
            }

            $qb->addSelect(sprintf('pf.%s AS %s', $column, $field));
        }

        $qb->andWhere($compiledFilter->sql);

        foreach ($compiledFilter->parameters as $name => $value) {
            $qb->setParameter($name, $value);
        }

        $this->sortSqlBuilder->apply($qb, $plan, 'pf');

        $qb
            ->setFirstResult($plan->offset)
            ->setMaxResults($plan->limit);

        return $qb->executeQuery()->fetchAllAssociative();
    }
}
