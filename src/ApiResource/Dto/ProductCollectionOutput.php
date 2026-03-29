<?php
declare(strict_types=1);

namespace App\ApiResource\Dto;

final class ProductCollectionOutput
{
    /**
     * @param ProductOutput[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $limit,
        public int $offset,
    ) {
    }
}
