<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

final readonly class DnfNormalizationResult
{
    /**
     * @param list<DnfBranch> $branches
     */
    public function __construct(
        public bool $shouldFallback,
        public array $branches = [],
    ) {
    }

    /**
     * @param list<DnfBranch> $branches
     */
    public static function success(array $branches): self
    {
        return new self(false, $branches);
    }

    public static function fallback(): self
    {
        return new self(true, []);
    }
}
