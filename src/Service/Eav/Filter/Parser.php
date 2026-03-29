<?php
declare(strict_types=1);

namespace App\Service\Eav\Filter;

use App\Service\Eav\Filter\Ast\ConditionNode;
use App\Service\Eav\Filter\Ast\GroupNode;
use App\Service\Eav\Filter\Ast\Node;

final class Parser
{
    /** @var list<Token> */
    private array $tokens = [];
    private int $position = 0;

    public function __construct(
        private readonly Tokenizer $tokenizer
    ) {
    }

    public function parse(string $input): Node
    {
        $this->tokens = $this->tokenizer->tokenize($input);
        $this->position = 0;

        $node = $this->parseOrExpression();
        $this->expect(Token::EOF);

        return $node;
    }

    private function parseOrExpression(): Node
    {
        $nodes = [$this->parseAndExpression()];

        while ($this->match(Token::OR)) {
            $nodes[] = $this->parseAndExpression();
        }

        return count($nodes) === 1 ? $nodes[0] : new GroupNode('OR', $nodes);
    }

    private function parseAndExpression(): Node
    {
        $nodes = [$this->parsePrimary()];

        while ($this->match(Token::AND)) {
            $nodes[] = $this->parsePrimary();
        }

        return count($nodes) === 1 ? $nodes[0] : new GroupNode('AND', $nodes);
    }

    private function parsePrimary(): Node
    {
        if ($this->match(Token::LPAREN)) {
            $node = $this->parseOrExpression();
            $this->expect(Token::RPAREN);

            return $node;
        }

        return $this->parseCondition();
    }

    private function parseCondition(): ConditionNode
    {
        $field = $this->expect(Token::IDENTIFIER)->value;
        $operator = $this->expect(Token::OPERATOR)->value;
        $value = $this->expect(Token::VALUE)->value;

        return new ConditionNode($field, $operator, $value);
    }

    private function match(string $type): bool
    {
        if ($this->current()->type === $type) {
            $this->position++;
            return true;
        }

        return false;
    }

    private function expect(string $type): Token
    {
        $token = $this->current();

        if ($token->type !== $type) {
            throw new \InvalidArgumentException(sprintf(
                'Expected token %s, got %s at position %d',
                $type,
                $token->type,
                $token->position
            ));
        }

        $this->position++;

        return $token;
    }

    private function current(): Token
    {
        return $this->tokens[$this->position];
    }
}
