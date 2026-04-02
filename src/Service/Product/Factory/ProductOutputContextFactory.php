<?php
declare(strict_types=1);

namespace App\Service\Product\Factory;

use App\Service\Product\Collection\ProductCollectionContext;

final class ProductOutputContextFactory
{
    public function createAllFieldsContext(): ProductCollectionContext
    {
        return new ProductCollectionContext(
            filter: null,
            selectedFields: ['*'],
            sorts: [],
            limit: 1,
            offset: 0,
            page: 1,
        );
    }
}
