<?php
declare(strict_types=1);

namespace App\Service\Product\Flat;

interface ProductFlatUpdaterInterface
{
    public function updateProduct(int $productId): void;

    public function deleteProduct(int $productId): void;
}
