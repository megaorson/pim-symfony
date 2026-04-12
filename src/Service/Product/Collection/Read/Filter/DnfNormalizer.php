<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Service\Eav\Filter\Ast\ConditionNode;
use App\Service\Eav\Filter\Ast\GroupNode;
use App\Service\Eav\Filter\Ast\Node;

final readonly class DnfNormalizer
{
    public function __construct(
        private int $maxBranches = 8,
        private int $maxConditionsPerBranch = 12,
    ) {
    }

    public function normalize(Node $node): DnfNormalizationResult
    {
        $branches = $this->normalizeNode($node);

        if ($branches === null) {
            return DnfNormalizationResult::fallback();
        }

        $branchCount = count($branches);

        if ($branchCount > $this->maxBranches) {
            return DnfNormalizationResult::fallback();
        }

        foreach ($branches as $branch) {
            if (count($branch->conditions) > $this->maxConditionsPerBranch) {
                return DnfNormalizationResult::fallback();
            }
        }

        return DnfNormalizationResult::success($branches);
    }

    /**
     * @return list<DnfBranch>|null
     */
    private function normalizeNode(Node $node): ?array
    {
        if ($node instanceof ConditionNode) {
            return [new DnfBranch([$node])];
        }

        if (!$node instanceof GroupNode) {
            return null;
        }

        $type = strtoupper($node->type);

        return match ($type) {
            'AND' => $this->normalizeAnd($node),
            'OR' => $this->normalizeOr($node),
            default => null,
        };
    }

    /**
     * @return list<DnfBranch>|null
     */
    private function normalizeOr(GroupNode $group): ?array
    {
        $result = [];

        foreach ($group->children as $child) {
            $childBranches = $this->normalizeNode($child);

            if ($childBranches === null) {
                return null;
            }

            foreach ($childBranches as $branch) {
                $result[] = $branch;

                if (count($result) > $this->maxBranches) {
                    return null;
                }
            }
        }

        return $result;
    }

    /**
     * @return list<DnfBranch>|null
     */
    private function normalizeAnd(GroupNode $group): ?array
    {
        $accumulator = [new DnfBranch([])];

        foreach ($group->children as $child) {
            $childBranches = $this->normalizeNode($child);

            if ($childBranches === null) {
                return null;
            }

            $next = [];

            foreach ($accumulator as $leftBranch) {
                foreach ($childBranches as $rightBranch) {
                    $mergedConditions = array_merge($leftBranch->conditions, $rightBranch->conditions);

                    if (count($mergedConditions) > $this->maxConditionsPerBranch) {
                        return null;
                    }

                    $next[] = new DnfBranch($mergedConditions);

                    if (count($next) > $this->maxBranches) {
                        return null;
                    }
                }
            }

            $accumulator = $next;
        }

        return $accumulator;
    }
}
