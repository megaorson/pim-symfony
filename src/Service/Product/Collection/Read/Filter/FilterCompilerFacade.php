<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Eav\Filter\FieldCollector;
use App\Service\Eav\Filter\Parser;
use App\Service\Eav\Filter\Tokenizer;
use App\Service\Product\Collection\Read\ProductCollectionQueryPlan;

final readonly class FilterCompilerFacade
{
    public function __construct(
        private Tokenizer $tokenizer,
        private Parser $parser,
        private FieldCollector $fieldCollector,
        private FilterSqlCompiler $filterSqlCompiler,
    ) {
    }

    public function compile(ProductCollectionQueryPlan $plan): CompiledFilter
    {
        if ($plan->rawFilter === null || trim($plan->rawFilter) === '') {
            return CompiledFilter::empty();
        }

        $tokens = $this->tokenizer->tokenize($plan->rawFilter);
        $ast = $this->parser->parse($tokens);

        $this->fieldCollector->collect($ast);

        return $this->filterSqlCompiler->compile($ast);
    }
}
