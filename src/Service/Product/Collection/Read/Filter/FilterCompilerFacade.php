<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Product\Collection\Read\ProductCollectionQueryPlan;

final readonly class FilterCompilerFacade
{
    public function __construct(
        private ParsedFilterAstFactory $parsedFilterAstFactory,
        private FilterSqlCompiler $filterSqlCompiler,
    ) {
    }

    public function compile(ProductCollectionQueryPlan $plan): CompiledFilter
    {
        $ast = $this->parsedFilterAstFactory->create($plan->rawFilter);
        if ($ast === null) {
            return CompiledFilter::empty();
        }
        return $this->filterSqlCompiler->compile($ast);
    }
}
