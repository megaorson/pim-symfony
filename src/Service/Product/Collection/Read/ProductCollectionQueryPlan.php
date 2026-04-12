<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read;

use App\Service\Eav\Dto\AttributeMetadata;

/**
 * @phpstan-type NormalizedSort array{field: string, direction: 'ASC'|'DESC'}
 */
final readonly class ProductCollectionQueryPlan
{
    /**
     * @param list<string> $selectedSystemFields
     * @param array<string, AttributeMetadata> $selectedAttributesByCode
     * @param list<NormalizedSort> $sorts
     * @param array<string, AttributeMetadata> $sortAttributesByCode
     */
    public function __construct(
        public array $selectedSystemFields,
        public array $selectedAttributesByCode,
        public array $sorts,
        public array $sortAttributesByCode,
        public ?string $rawFilter,
        public bool $selectAllFields,
        public bool $hasExplicitSelect,
        public int $limit,
        public int $offset,
        public int $page,
    ) {
    }

    public function shouldLoadAllAttributes(): bool
    {
        return $this->selectAllFields;
    }
}
