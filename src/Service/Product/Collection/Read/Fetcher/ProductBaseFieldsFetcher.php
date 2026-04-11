<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Fetcher;

use App\Service\Product\Collection\Read\ProductCollectionQueryPlan;
use App\Service\Product\Field\ProductSystemFieldRegistry;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class ProductBaseFieldsFetcher
{
    public function __construct(
        private Connection $connection,
        private ProductSystemFieldRegistry $systemFieldRegistry,
    ) {
    }

    /**
     * @param list<int> $ids
     * @return array<int, array<string, mixed>>
     */
    public function fetchByIds(array $ids, ProductCollectionQueryPlan $plan): array
    {
        if ($ids === []) {
            return [];
        }

        $fields = $this->resolveFieldsToLoad($plan);

        $qb = $this->connection->createQueryBuilder();
        $qb->from('product', 'p');

        foreach ($fields as $field) {
            if (!$this->systemFieldRegistry->isSystemField($field)) {
                continue;
            }

            $column = $this->systemFieldRegistry->getDoctrineField($field);

            $qb->addSelect(sprintf('p.%s AS %s', $column, $field));
        }

        $qb
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER);

        $rows = $qb->executeQuery()->fetchAllAssociative();

        $indexed = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $indexed[$id] = $row;
        }

        return $indexed;
    }

    /**
     * @return list<string>
     */
    private function resolveFieldsToLoad(ProductCollectionQueryPlan $plan): array
    {
        $fields = ['id'];

        if ($plan->selectAllFields) {
            $fields[] = 'sku';
            $fields[] = 'created_at';
            $fields[] = 'updated_at';

            return array_values(array_unique($fields));
        }

        if ($plan->selectedSystemFields !== []) {
            foreach ($plan->selectedSystemFields as $field) {
                if ($field === 'id') {
                    continue;
                }

                $fields[] = $field;
            }

            return array_values(array_unique($fields));
        }

        if (!$plan->hasExplicitSelect) {
            $fields[] = 'sku';
        }

        return array_values(array_unique($fields));
    }
}
