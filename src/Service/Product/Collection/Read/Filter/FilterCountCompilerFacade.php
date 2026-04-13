<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Product\Collection\Read\ProductCollectionQueryPlan;

final readonly class FilterCountCompilerFacade
{
    public function __construct(
        private ParsedFilterAstFactory $parsedFilterAstFactory,
        private FilterCountSqlBuilder $filterCountSqlBuilder,
    ) {
    }

    public function compile(ProductCollectionQueryPlan $plan): CompiledFilter
    {
        $ast = $this->parsedFilterAstFactory->create($plan->rawFilter);
        if ($ast === null) {
            return new CompiledFilter('SELECT COUNT(*) FROM product p', []);
        }
        return $this->filterCountSqlBuilder->buildCount($ast);
    }
}
