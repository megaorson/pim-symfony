<?php
declare(strict_types=1);

namespace App\Service\Eav\Filter;

use App\Exception\Api\InvalidFilterException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Tokenizer
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return list<Token>
     */
    public function tokenize(string $input): array
    {
        $length = strlen($input);
        $position = 0;
        $tokens = [];

        while ($position < $length) {
            $char = $input[$position];

            if ($this->isWhitespace($char)) {
                $position++;
                continue;
            }

            if ($char === '(') {
                $tokens[] = new Token(Token::LPAREN, '(', $position);
                $position++;
                continue;
            }

            if ($char === ')') {
                $tokens[] = new Token(Token::RPAREN, ')', $position);
                $position++;
                continue;
            }

            if ($char === ',') {
                $tokens[] = new Token(Token::COMMA, ',', $position);
                $position++;
                continue;
            }

            if ($char === '"' || $char === '\'') {
                $tokens[] = $this->readQuotedString($input, $position, $char);
                continue;
            }

            if ($this->isIdentifierStart($char)) {
                $tokens[] = $this->readIdentifierOrKeyword($input, $position);
                continue;
            }

            if ($this->isDigit($char) || $char === '-' || $char === '.') {
                $tokens[] = $this->readValue($input, $position);
                continue;
            }

            throw new InvalidFilterException(
                $this->translator->trans('eav.filter.unexpected_character', [
                    '%character%' => $char,
                    '%position%' => (string) $position,
                ])
            );
        }

        $tokens[] = new Token(Token::EOF, '', $position);

        return $tokens;
    }

    private function readQuotedString(string $input, int &$position, string $quote): Token
    {
        $start = $position;
        $position++;
        $length = strlen($input);
        $value = '';

        while ($position < $length) {
            $char = $input[$position];

            if ($char === '\\') {
                $position++;

                if ($position >= $length) {
                    break;
                }

                $value .= $input[$position];
                $position++;
                continue;
            }

            if ($char === $quote) {
                $position++;

                return new Token(Token::VALUE, $value, $start);
            }

            $value .= $char;
            $position++;
        }

        throw new InvalidFilterException(
            $this->translator->trans('eav.filter.unterminated_string', [
                '%position%' => (string) $start,
            ])
        );
    }

    private function readIdentifierOrKeyword(string $input, int &$position): Token
    {
        $start = $position;
        $length = strlen($input);
        $value = '';

        while ($position < $length) {
            $char = $input[$position];

            if (preg_match('/[A-Za-z0-9_]/', $char) !== 1) {
                break;
            }

            $value .= $char;
            $position++;
        }

        $upperValue = strtoupper($value);

        if ($upperValue === 'AND') {
            return new Token(Token::AND, 'AND', $start);
        }

        if ($upperValue === 'OR') {
            return new Token(Token::OR, 'OR', $start);
        }

        if ($upperValue === 'IN') {
            return new Token(Token::IN, 'IN', $start);
        }

        if (in_array($upperValue, ['EQ', 'NE', 'GT', 'GE', 'LT', 'LE', 'BEGINS'], true)) {
            return new Token(Token::OPERATOR, $upperValue, $start);
        }

        return new Token(Token::IDENTIFIER, $value, $start);
    }

    private function readValue(string $input, int &$position): Token
    {
        $start = $position;
        $length = strlen($input);
        $value = '';

        while ($position < $length) {
            $char = $input[$position];

            if (
                $this->isWhitespace($char)
                || $char === '('
                || $char === ')'
                || $char === ','
            ) {
                break;
            }

            $value .= $char;
            $position++;
        }

        return new Token(Token::VALUE, $value, $start);
    }

    private function isWhitespace(string $char): bool
    {
        return preg_match('/\s/', $char) === 1;
    }

    private function isDigit(string $char): bool
    {
        return preg_match('/\d/', $char) === 1;
    }

    private function isIdentifierStart(string $char): bool
    {
        return preg_match('/[A-Za-z_]/', $char) === 1;
    }
}
