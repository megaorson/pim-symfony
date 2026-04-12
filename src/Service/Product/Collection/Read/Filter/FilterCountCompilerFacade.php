<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Eav\Filter\Parser;
use App\Service\Eav\Filter\Tokenizer;
use App\Service\Product\Collection\Read\ProductCollectionQueryPlan;

final readonly class FilterCountCompilerFacade
{
    public function __construct(
        private Tokenizer $tokenizer,
        private Parser $parser,
        private FilterCountSqlBuilder $filterCountSqlBuilder,
    ) {
    }

    public function compile(ProductCollectionQueryPlan $plan): CompiledFilter
    {
        if ($plan->rawFilter === null || trim($plan->rawFilter) === '') {
            return new CompiledFilter('SELECT COUNT(*) FROM product p', []);
        }

        $tokens = $this->tokenizer->tokenize($plan->rawFilter);
        $ast = $this->parser->parse($tokens);

        return $this->filterCountSqlBuilder->buildCount($ast);
    }
}
