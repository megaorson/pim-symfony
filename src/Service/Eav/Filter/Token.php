<?php
declare(strict_types=1);

namespace App\Service\Eav\Filter;

final class Token
{
    public const IDENTIFIER = 'IDENTIFIER';
    public const OPERATOR = 'OPERATOR';
    public const VALUE = 'VALUE';
    public const AND = 'AND';
    public const OR = 'OR';
    public const LPAREN = 'LPAREN';
    public const RPAREN = 'RPAREN';
    public const EOF = 'EOF';

    public function __construct(
        public readonly string $type,
        public readonly string $value,
        public readonly int $position
    ) {
    }
}
