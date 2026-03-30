<?php
declare(strict_types=1);

namespace App\Service\Product\Collection;

use Doctrine\ORM\QueryBuilder;

interface CollectionApplierInterface
{
    public function apply(QueryBuilder $qb, ProductCollectionContext $context, string $rootAlias = 'p'): void;
}
