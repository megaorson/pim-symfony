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
        $this->collectFromNode($node, $fields);

        return array_values(array_keys($fields));
    }

    /**
     * @param array<string, true> $fields
     */
    private function collectFromNode(Node $node, array &$fields): void
    {
        if ($node instanceof ConditionNode) {
            $fields[$node->field] = true;

            return;
        }

        if (!$node instanceof GroupNode) {
            return;
        }

        foreach ($node->children as $child) {
            $this->collectFromNode($child, $fields);
        }
    }
}
