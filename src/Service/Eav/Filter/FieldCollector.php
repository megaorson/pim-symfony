<?php
declare(strict_types=1);

namespace App\Service\Eav\Filter;

use App\Service\Eav\Filter\Ast\ConditionNode;
use App\Service\Eav\Filter\Ast\GroupNode;
use App\Service\Eav\Filter\Ast\Node;

final class FieldCollector
{
    /**
     * @return list<string>
     */
    public function collect(Node $node): array
    {
        $fields = [];
        $this->walk($node, $fields);

        return array_values(array_unique($fields));
    }

    /**
     * @param array<int, string> $fields
     */
    private function walk(Node $node, array &$fields): void
    {
        if ($node instanceof ConditionNode) {
            $fields[] = $node->field;
            return;
        }

        if ($node instanceof GroupNode) {
            foreach ($node->children as $child) {
                $this->walk($child, $fields);
            }
        }
    }
}
