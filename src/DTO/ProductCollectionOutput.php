<?php
declare(strict_types=1);

namespace App\DTO;

class ProductCollectionOutput
{
    public int $totalItems;

    public int $page;

    public int $limit;

    /** @var ProductOutput[] */
    public array $items = [];
}
