<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Eav\Filter\Ast\ConditionNode;

final readonly class DnfBranch
{
    /**
     * @param list<ConditionNode> $conditions
     */
    public function __construct(
        public array $conditions,
    ) {
    }
}
