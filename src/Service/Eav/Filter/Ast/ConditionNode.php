<?php
declare(strict_types=1);

namespace App\Service\Eav\Filter\Ast;

final class ConditionNode implements Node
{
    public function __construct(
        public readonly string $field,
        public readonly string $operator,
        public readonly string $value
    ) {
    }
}
