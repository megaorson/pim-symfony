<?php
declare(strict_types=1);

namespace App\Service\Product\Flat\Read;

use App\Exception\Api\InvalidSortException;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ProductFlatSortSqlBuilder
{
    public function __construct(
        private ProductFlatFieldMap $fieldMap,
        private TranslatorInterface $translator,
    ) {
    }

    public function apply(
        QueryBuilder $qb,
        ProductFlatQueryPlan $plan,
        string $rootAlias = 'pf',
    ): void {
        $sortableMap = $this->fieldMap->getSortableFieldToColumnMap();

        foreach ($plan->sorts as $sort) {
            $field = $sort['field'];
            $direction = $sort['direction'];

            $column = $sortableMap[$field] ?? null;

            if ($column === null) {
                throw new InvalidSortException(
                    $this->translator->trans('eav.sort.unknown_field', ['%field%' => $field])
                );
            }

            $qb->addOrderBy(sprintf('%s.%s', $rootAlias, $column), $direction);
        }
    }
}
