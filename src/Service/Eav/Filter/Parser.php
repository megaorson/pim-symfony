<?php
declare(strict_types=1);

namespace App\Service\Eav\Filter;

use App\Exception\Api\InvalidFilterException;
use App\Service\Eav\Filter\Ast\ConditionNode;
use App\Service\Eav\Filter\Ast\GroupNode;
use App\Service\Eav\Filter\Ast\Node;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Parser
{
    /** @var list<Token> */
    private array $tokens = [];
    private int $position = 0;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @param list<Token> $tokens
     */
    public function parse(array $tokens): Node
    {
        $this->tokens = $tokens;
        $this->position = 0;

        $node = $this->parseExpression();

        if ($this->current()->type !== Token::EOF) {
            $token = $this->current();

            throw new InvalidFilterException(
                $this->translator->trans('eav.filter.unexpected_token', [
                    '%token%' => $token->value !== '' ? $token->value : $token->type,
                    '%position%' => (string) $token->position,
                ])
            );
        }

        return $node;
    }

    private function parseExpression(): Node
    {
        return $this->parseOr();
    }

    private function parseOr(): Node
    {
        $nodes = [$this->parseAnd()];

        while ($this->match(Token::OR)) {
            $nodes[] = $this->parseAnd();
        }

        if (count($nodes) === 1) {
            return $nodes[0];
        }

        return new GroupNode('OR', $nodes);
    }

    private function parseAnd(): Node
    {
        $nodes = [$this->parseFactor()];

        while ($this->match(Token::AND)) {
            $nodes[] = $this->parseFactor();
        }

        if (count($nodes) === 1) {
            return $nodes[0];
        }

        return new GroupNode('AND', $nodes);
    }

    private function parseFactor(): Node
    {
        if ($this->match(Token::LPAREN)) {
            $node = $this->parseExpression();
            $this->expect(Token::RPAREN);

            return $node;
        }

        return $this->parseCondition();
    }

    private function parseCondition(): ConditionNode
    {
        $fieldToken = $this->expect(Token::IDENTIFIER);
        $field = $fieldToken->value;

        if ($this->match(Token::IN)) {
            $this->expect(Token::LPAREN);
            $values = $this->parseInValues();
            $this->expect(Token::RPAREN);

            return new ConditionNode($field, 'IN', '(' . implode(',', $values) . ')');
        }

        $operatorToken = $this->expect(Token::OPERATOR);
        $valueToken = $this->expect(Token::VALUE);

        return new ConditionNode(
            $field,
            $operatorToken->value,
            $valueToken->value,
        );
    }

    /**
     * @return list<string>
     */
    private function parseInValues(): array
    {
        $values = [];
        $values[] = $this->expect(Token::VALUE)->value;

        while ($this->match(Token::COMMA)) {
            $values[] = $this->expect(Token::VALUE)->value;
        }

        return $values;
    }

    private function match(string $type): bool
    {
        if ($this->current()->type !== $type) {
            return false;
        }

        $this->position++;

        return true;
    }

    private function expect(string $type): Token
    {
        $token = $this->current();

        if ($token->type !== $type) {
            throw new InvalidFilterException(
                $this->translator->trans('eav.filter.expected_token', [
                    '%expected%' => $type,
                    '%actual%' => $token->type,
                    '%position%' => (string) $token->position,
                ])
            );
        }

        $this->position++;

        return $token;
    }

    private function current(): Token
    {
        return $this->tokens[$this->position];
    }
}
