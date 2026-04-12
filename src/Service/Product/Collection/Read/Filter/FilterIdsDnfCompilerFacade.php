<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Eav\Filter\Parser;
use App\Service\Eav\Filter\Tokenizer;
use App\Service\Product\Collection\Read\ProductCollectionQueryPlan;

final readonly class FilterIdsDnfCompilerFacade
{
    public function __construct(
        private Tokenizer $tokenizer,
        private Parser $parser,
        private DnfNormalizer $dnfNormalizer,
        private FilterIdsDnfSqlBuilder $filterIdsDnfSqlBuilder,
    ) {
    }

    public function tryCompile(ProductCollectionQueryPlan $plan): ?OptimizedFilterSource
    {
        if ($plan->rawFilter === null || trim($plan->rawFilter) === '') {
            return null;
        }

        $tokens = $this->tokenizer->tokenize($plan->rawFilter);
        $ast = $this->parser->parse($tokens);

        $normalized = $this->dnfNormalizer->normalize($ast);

        if ($normalized->shouldFallback) {
            return null;
        }

        return $this->filterIdsDnfSqlBuilder->build($normalized->branches);
    }
}
