<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Eav\Filter\Parser;
use App\Service\Eav\Filter\Tokenizer;
use App\Service\Product\Collection\Read\ProductCollectionQueryPlan;

final readonly class FilterIdsCompilerFacade
{
    public function __construct(
        private Tokenizer $tokenizer,
        private Parser $parser,
        private FilterIdsSqlBuilder $filterIdsSqlBuilder,
    ) {
    }

    public function tryCompile(ProductCollectionQueryPlan $plan): ?OptimizedFilterSource
    {
        if ($plan->rawFilter === null || trim($plan->rawFilter) === '') {
            return null;
        }

        $tokens = $this->tokenizer->tokenize($plan->rawFilter);
        $ast = $this->parser->parse($tokens);

        if (!$this->filterIdsSqlBuilder->canOptimize($ast)) {
            return null;
        }

        return $this->filterIdsSqlBuilder->build($ast);
    }
}
