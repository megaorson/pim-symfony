<?php
declare(strict_types=1);

namespace App\Service\Eav\Filter;

use Symfony\Contracts\Translation\TranslatorInterface;
use App\Exception\Api\InvalidFilterException;

final class Tokenizer
{
    private const OPERATORS = ['EQ', 'NE', 'GT', 'GE', 'LT', 'LE', 'IN', 'BEGINS'];

    private string $input = '';
    private int $length = 0;
    private int $position = 0;

    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * @return list<Token>
     */
    public function tokenize(string $input): array
    {
        $this->input = trim($input);
        $this->length = strlen($this->input);
        $this->position = 0;

        $tokens = [];

        while (!$this->isEnd()) {
            $this->skipWhitespace();

            if ($this->isEnd()) {
                break;
            }

            $char = $this->currentChar();

            if ($char === '(') {
                $tokens[] = new Token(Token::LPAREN, '(', $this->position);
                $this->position++;
                continue;
            }

            if ($char === ')') {
                $tokens[] = new Token(Token::RPAREN, ')', $this->position);
                $this->position++;
                continue;
            }

            $word = $this->readWord();

            if ($word !== '') {
                $upperWord = strtoupper($word);
                $tokenPosition = $this->position - strlen($word);

                if ($upperWord === 'AND') {
                    $tokens[] = new Token(Token::AND, 'AND', $tokenPosition);
                    continue;
                }

                if ($upperWord === 'OR') {
                    $tokens[] = new Token(Token::OR, 'OR', $tokenPosition);
                    continue;
                }

                if (in_array($upperWord, self::OPERATORS, true)) {
                    $tokens[] = new Token(Token::OPERATOR, $upperWord, $tokenPosition);

                    $this->skipWhitespace();
                    $valueStart = $this->position;
                    $value = $this->readValue($upperWord);

                    if ($value === '') {
                        throw new InvalidFilterException($this->translator->trans(
                            'eav.filter.expected_value_after_operator',
                            [
                                '%operator%' => $upperWord,
                                '%position%' => (string) $valueStart,
                            ]
                        ));
                    }

                    $tokens[] = new Token(Token::VALUE, $value, $valueStart);
                    continue;
                }

                $tokens[] = new Token(Token::IDENTIFIER, $word, $tokenPosition);
                continue;
            }

            throw new InvalidFilterException($this->translator->trans(
                'eav.filter.unexpected_character',
                [
                    '%character%' => $char,
                    '%position%' => (string) $this->position,
                ]
            ));
        }

        $tokens[] = new Token(Token::EOF, '', $this->position);

        return $tokens;
    }

    private function readValue(string $operator): string
    {
        if ($operator === 'IN') {
            return $this->readInValue();
        }

        if ($this->isEnd()) {
            return '';
        }

        $char = $this->currentChar();

        if ($char === "'" || $char === '"') {
            return $this->readQuotedString();
        }

        $start = $this->position;

        while (!$this->isEnd()) {
            if ($this->currentChar() === ')' || $this->isLogicalAhead()) {
                break;
            }

            $this->position++;
        }

        return trim(substr($this->input, $start, $this->position - $start));
    }

    private function readInValue(): string
    {
        $this->skipWhitespace();

        if ($this->isEnd() || $this->currentChar() !== '(') {
            throw new InvalidFilterException($this->translator->trans(
                'eav.filter.expected_open_parenthesis_after_in',
                ['%position%' => (string) $this->position]
            ));
        }

        $start = $this->position;
        $depth = 0;
        $inQuote = false;
        $quoteChar = null;

        while (!$this->isEnd()) {
            $char = $this->currentChar();

            if ($inQuote) {
                if ($char === '\\') {
                    $this->position++;
                    if (!$this->isEnd()) {
                        $this->position++;
                    }
                    continue;
                }

                if ($char === $quoteChar) {
                    $inQuote = false;
                    $quoteChar = null;
                }

                $this->position++;
                continue;
            }

            if ($char === "'" || $char === '"') {
                $inQuote = true;
                $quoteChar = $char;
                $this->position++;
                continue;
            }

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
                $this->position++;

                if ($depth === 0) {
                    break;
                }

                continue;
            }

            $this->position++;
        }

        if ($depth !== 0) {
            throw new InvalidFilterException($this->translator->trans(
                'eav.filter.unterminated_in_expression',
                ['%position%' => (string) $start]
            ));
        }

        return trim(substr($this->input, $start, $this->position - $start));
    }

    private function readQuotedString(): string
    {
        $quote = $this->currentChar();
        $this->position++;

        $buffer = '';

        while (!$this->isEnd()) {
            $char = $this->currentChar();

            if ($char === '\\') {
                $this->position++;
                if (!$this->isEnd()) {
                    $buffer .= $this->currentChar();
                    $this->position++;
                }
                continue;
            }

            if ($char === $quote) {
                $this->position++;
                return $buffer;
            }

            $buffer .= $char;
            $this->position++;
        }

        throw new InvalidFilterException($this->translator->trans('eav.filter.unterminated_quoted_string'));
    }

    private function readWord(): string
    {
        if ($this->isEnd()) {
            return '';
        }

        $start = $this->position;

        while (!$this->isEnd()) {
            $char = $this->currentChar();

            if (preg_match('/[a-zA-Z0-9_]/', $char) !== 1) {
                break;
            }

            $this->position++;
        }

        return substr($this->input, $start, $this->position - $start);
    }

    private function isLogicalAhead(): bool
    {
        $remaining = substr($this->input, $this->position);

        return preg_match('/^(AND|OR)(\s|\(|\)|$)/', $remaining) === 1;
    }

    private function skipWhitespace(): void
    {
        while (!$this->isEnd() && preg_match('/\s/', $this->currentChar()) === 1) {
            $this->position++;
        }
    }

    private function currentChar(): string
    {
        return $this->input[$this->position];
    }

    private function isEnd(): bool
    {
        return $this->position >= $this->length;
    }
}
