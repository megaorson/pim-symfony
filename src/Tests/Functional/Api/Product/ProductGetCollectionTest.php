<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Product;

use App\Tests\Functional\Support\ProductApiTestCase;

final class ProductGetCollectionTest extends ProductApiTestCase
{
    public function testGetProductCollectionReturnsEmptyCollection(): void
    {
        $this->createDefaultProductAttributes();

        $this->jsonGet('/api/products');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('total', $data);
        self::assertArrayHasKey('limit', $data);
        self::assertArrayHasKey('offset', $data);

        self::assertSame([], $data['items']);
        self::assertSame(0, $data['total']);
        self::assertGreaterThanOrEqual(0, $data['limit']);
        self::assertSame(0, $data['offset']);
    }

    public function testGetProductCollectionReturnsCreatedProducts(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-COL-001',
            attributes: [
                'name' => 'Collection Product 1',
                'price' => 100.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-COL-002',
            attributes: [
                'name' => 'Collection Product 2',
                'price' => 200.00,
                'qty' => 5,
            ],
        );

        $this->jsonGet('/api/products?sort=sku');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('total', $data);
        self::assertArrayHasKey('limit', $data);
        self::assertArrayHasKey('offset', $data);

        self::assertCount(2, $data['items']);
        self::assertSame(2, $data['total']);

        $first = $this->findCollectionItemBySku($data['items'], 'SKU-COL-001');
        $second = $this->findCollectionItemBySku($data['items'], 'SKU-COL-002');

        $this->assertProductResponseContains($first, 'SKU-COL-001', [
            'name' => 'Collection Product 1',
            'price' => 100.00,
        ]);

        $this->assertProductResponseDoesNotContainAttribute($first, 'qty');

        $this->assertProductResponseContains($second, 'SKU-COL-002', [
            'name' => 'Collection Product 2',
            'price' => 200.00,
            'qty' => 5,
        ]);
    }

    public function testGetProductCollectionSupportsPagination(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-PAGE-001',
            attributes: [
                'name' => 'Product 1',
                'price' => 10.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-PAGE-002',
            attributes: [
                'name' => 'Product 2',
                'price' => 20.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-PAGE-003',
            attributes: [
                'name' => 'Product 3',
                'price' => 30.00,
            ],
        );

        $this->jsonGet('/api/products?page=2&limit=2&sort=sku');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('total', $data);
        self::assertArrayHasKey('limit', $data);
        self::assertArrayHasKey('offset', $data);

        self::assertCount(1, $data['items']);
        self::assertSame(3, $data['total']);
        self::assertSame(2, $data['limit']);
        self::assertSame(2, $data['offset']);

        $item = $data['items'][0];

        $this->assertProductResponseContains($item, 'SKU-PAGE-003', [
            'name' => 'Product 3',
            'price' => 30.00,
        ]);
    }

    private function findCollectionItemBySku(array $items, string $sku): array
    {
        foreach ($items as $item) {
            if (($item['attributes']['sku'] ?? null) === $sku) {
                return $item;
            }
        }

        self::fail(sprintf('Product with sku "%s" was not found in collection.', $sku));
    }
}
