<?php
declare(strict_types=1);

namespace App\Service\Eav\Filter\Ast;

final class GroupNode implements Node
{
    /**
     * @param list<Node> $children
     */
    public function __construct(
        public readonly string $type,
        public readonly array $children
    ) {
    }
}
