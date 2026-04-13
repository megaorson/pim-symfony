<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Product\Collection\Read\ProductCollectionQueryPlan;

final readonly class FilterIdsDnfCompilerFacade
{
    public function __construct(
        private ParsedFilterAstFactory $parsedFilterAstFactory,
        private DnfNormalizer $dnfNormalizer,
        private FilterIdsDnfSqlBuilder $filterIdsDnfSqlBuilder,
    ) {
    }

    public function tryCompile(ProductCollectionQueryPlan $plan): ?OptimizedFilterSource
    {
        $ast = $this->parsedFilterAstFactory->create($plan->rawFilter);
        if ($ast === null) {
            return null;
        }

        $normalized = $this->dnfNormalizer->normalize($ast);
        if ($normalized->shouldFallback) {
            return null;
        }

        return $this->filterIdsDnfSqlBuilder->build($normalized->branches);
    }
}
