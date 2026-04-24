<?php
declare(strict_types=1);

namespace App\Service\Product\Flat\Read;

/**
 * @phpstan-type NormalizedSort array{field: string, direction: 'ASC'|'DESC'}
 */
final readonly class ProductFlatQueryPlan
{
    /**
     * @param list<string> $selectedFields
     * @param list<NormalizedSort> $sorts
     */
    public function __construct(
        public array $selectedFields,
        public array $sorts,
        public ?string $rawFilter,
        public int $limit,
        public int $offset,
        public int $page,
    ) {
    }
}
