<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Product;

use App\Tests\Functional\Support\ProductApiTestCase;

final class ProductSortTest extends ProductApiTestCase
{
    public function testGetProductCollectionSortsByPriceDescending(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-SORT-001',
            attributes: [
                'name' => 'Cheap Product',
                'price' => 10.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-SORT-002',
            attributes: [
                'name' => 'Mid Product',
                'price' => 20.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-SORT-003',
            attributes: [
                'name' => 'Expensive Product',
                'price' => 30.00,
            ],
        );

        $this->jsonGet('/api/products?sort=-price');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertCount(3, $data['items']);

        $this->assertProductResponseContains($data['items'][0], 'SKU-SORT-003', [
            'name' => 'Expensive Product',
            'price' => 30.00,
        ]);

        $this->assertProductResponseContains($data['items'][1], 'SKU-SORT-002', [
            'name' => 'Mid Product',
            'price' => 20.00,
        ]);

        $this->assertProductResponseContains($data['items'][2], 'SKU-SORT-001', [
            'name' => 'Cheap Product',
            'price' => 10.00,
        ]);
    }

    public function testGetProductCollectionSortsByNameAscAndPriceDesc(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-MULTI-001',
            attributes: [
                'name' => 'Alpha',
                'price' => 10.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-MULTI-002',
            attributes: [
                'name' => 'Alpha',
                'price' => 30.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-MULTI-003',
            attributes: [
                'name' => 'Beta',
                'price' => 20.00,
            ],
        );

        $this->jsonGet('/api/products?sort=name,-price');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertCount(3, $data['items']);

        $this->assertProductResponseContains($data['items'][0], 'SKU-MULTI-002', [
            'name' => 'Alpha',
            'price' => 30.00,
        ]);

        $this->assertProductResponseContains($data['items'][1], 'SKU-MULTI-001', [
            'name' => 'Alpha',
            'price' => 10.00,
        ]);

        $this->assertProductResponseContains($data['items'][2], 'SKU-MULTI-003', [
            'name' => 'Beta',
            'price' => 20.00,
        ]);
    }
}
