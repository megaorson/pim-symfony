<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Eav\Filter\Ast\Node;
use App\Service\Eav\Filter\FieldCollector;
use App\Service\Eav\Filter\Parser;
use App\Service\Eav\Filter\Tokenizer;

final readonly class ParsedFilterAstFactory
{
    public function __construct(
        private Tokenizer $tokenizer,
        private Parser $parser,
        private ?FieldCollector $fieldCollector = null,
    ) {
    }

    public function create(?string $rawFilter): ?Node
    {
        if ($rawFilter === null || trim($rawFilter) === '') {
            return null;
        }

        $tokens = $this->tokenizer->tokenize($rawFilter);
        $ast = $this->parser->parse($tokens);
        $this->fieldCollector?->collect($ast);

        return $ast;
    }
}
