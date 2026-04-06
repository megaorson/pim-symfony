<?php
declare(strict_types=1);

namespace App\ApiResource\Dto;

final class ProductAttributeCollectionOutput
{
    /**
     * @param ProductAttributeOutput[] $items
     */
    public function __construct(
        public array $items,
        public int $totalItems,
        public int $page,
        public int $limit,
        public int $offset,
    ) {
    }
}
