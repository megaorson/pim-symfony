<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Product;

use App\Tests\Functional\Support\ProductApiTestCase;

final class ProductSelectTest extends ProductApiTestCase
{
    public function testGetProductCollectionSelectsOnlyRequestedFields(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-SELECT-COL-001',
            attributes: [
                'name' => 'Collection Select 1',
                'price' => 100.00,
                'qty' => 1,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-SELECT-COL-002',
            attributes: [
                'name' => 'Collection Select 2',
                'price' => 200.00,
                'qty' => 2,
            ],
        );

        $this->jsonGet('/api/products?sort=sku&select=sku,name');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(2, $data['total']);
        self::assertCount(2, $data['items']);

        $first = $this->findCollectionItemBySku($data['items'], 'SKU-SELECT-COL-001');
        $second = $this->findCollectionItemBySku($data['items'], 'SKU-SELECT-COL-002');

        self::assertCount(2, $first['attributes']);
        self::assertSame('SKU-SELECT-COL-001', $first['attributes']['sku']);
        self::assertSame('Collection Select 1', $first['attributes']['name']);
        self::assertArrayNotHasKey('price', $first['attributes']);
        self::assertArrayNotHasKey('qty', $first['attributes']);

        self::assertCount(2, $second['attributes']);
        self::assertSame('SKU-SELECT-COL-002', $second['attributes']['sku']);
        self::assertSame('Collection Select 2', $second['attributes']['name']);
        self::assertArrayNotHasKey('price', $second['attributes']);
        self::assertArrayNotHasKey('qty', $second['attributes']);
    }

    public function testGetProductCollectionSelectsSystemAndEavFields(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-SELECT-COL-003',
            attributes: [
                'name' => 'Mixed Select 1',
                'price' => 300.00,
                'qty' => 3,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-SELECT-COL-004',
            attributes: [
                'name' => 'Mixed Select 2',
                'price' => 400.00,
                'qty' => 4,
            ],
        );

        $this->jsonGet('/api/products?sort=sku&select=sku,price');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(2, $data['total']);
        self::assertCount(2, $data['items']);

        $first = $this->findCollectionItemBySku($data['items'], 'SKU-SELECT-COL-003');
        $second = $this->findCollectionItemBySku($data['items'], 'SKU-SELECT-COL-004');

        self::assertCount(2, $first['attributes']);
        self::assertSame('SKU-SELECT-COL-003', $first['attributes']['sku']);
        self::assertSame(300.00, $first['attributes']['price']);
        self::assertArrayNotHasKey('name', $first['attributes']);
        self::assertArrayNotHasKey('qty', $first['attributes']);

        self::assertCount(2, $second['attributes']);
        self::assertSame('SKU-SELECT-COL-004', $second['attributes']['sku']);
        self::assertSame(400.00, $second['attributes']['price']);
        self::assertArrayNotHasKey('name', $second['attributes']);
        self::assertArrayNotHasKey('qty', $second['attributes']);
    }

    public function testGetProductCollectionSelectAllReturnsAllFields(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-SELECT-COL-005',
            attributes: [
                'name' => 'Collection All 1',
                'price' => 500.00,
                'qty' => 5,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-SELECT-COL-006',
            attributes: [
                'name' => 'Collection All 2',
                'price' => 600.00,
                'qty' => 6,
            ],
        );

        $this->jsonGet('/api/products?sort=sku&select=*');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(2, $data['total']);
        self::assertCount(2, $data['items']);

        $first = $this->findCollectionItemBySku($data['items'], 'SKU-SELECT-COL-005');
        $second = $this->findCollectionItemBySku($data['items'], 'SKU-SELECT-COL-006');

        self::assertArrayHasKey('id', $first);
        self::assertArrayHasKey('attributes', $first);
        self::assertSame('SKU-SELECT-COL-005', $first['attributes']['sku']);
        self::assertSame('Collection All 1', $first['attributes']['name']);
        self::assertSame(500.00, $first['attributes']['price']);
        self::assertSame(5, $first['attributes']['qty']);

        self::assertArrayHasKey('id', $second);
        self::assertArrayHasKey('attributes', $second);
        self::assertSame('SKU-SELECT-COL-006', $second['attributes']['sku']);
        self::assertSame('Collection All 2', $second['attributes']['name']);
        self::assertSame(600.00, $second['attributes']['price']);
        self::assertSame(6, $second['attributes']['qty']);
    }

    public function testGetProductCollectionSelectsOnlyOneField(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-SELECT-COL-007',
            attributes: [
                'name' => 'Single Field 1',
                'price' => 700.00,
                'qty' => 7,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-SELECT-COL-008',
            attributes: [
                'name' => 'Single Field 2',
                'price' => 800.00,
                'qty' => 8,
            ],
        );

        $this->jsonGet('/api/products?sort=sku&select=name');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(2, $data['total']);
        self::assertCount(2, $data['items']);

        $first = $this->findCollectionItemBySkuFromName($data['items'], 'Single Field 1');
        $second = $this->findCollectionItemBySkuFromName($data['items'], 'Single Field 2');

        self::assertCount(1, $first['attributes']);
        self::assertSame('Single Field 1', $first['attributes']['name']);

        self::assertCount(1, $second['attributes']);
        self::assertSame('Single Field 2', $second['attributes']['name']);
    }

    public function testGetProductCollectionReturns400ForUnknownSelectedField(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-SELECT-COL-009',
            attributes: [
                'name' => 'Collection Broken',
                'price' => 900.00,
            ],
        );

        $this->jsonGet('/api/products?select=sku,unknownField');

        self::assertResponseStatusCodeSame(400);
    }

    public function testGetProductCollectionSupportsSelectWithPagination(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-SELECT-PAGE-001',
            attributes: [
                'name' => 'Page Product 1',
                'price' => 100.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-SELECT-PAGE-002',
            attributes: [
                'name' => 'Page Product 2',
                'price' => 200.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-SELECT-PAGE-003',
            attributes: [
                'name' => 'Page Product 3',
                'price' => 300.00,
            ],
        );

        $this->jsonGet('/api/products?page=2&limit=2&sort=sku&select=sku,name');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(3, $data['total']);
        self::assertSame(2, $data['limit']);
        self::assertSame(2, $data['offset']);
        self::assertCount(1, $data['items']);

        $item = $data['items'][0];

        self::assertCount(2, $item['attributes']);
        self::assertSame('SKU-SELECT-PAGE-003', $item['attributes']['sku']);
        self::assertSame('Page Product 3', $item['attributes']['name']);
        self::assertArrayNotHasKey('price', $item['attributes']);
    }

    protected function findCollectionItemBySkuFromName(array $items, string $name): array
    {
        foreach ($items as $item) {
            if (($item['attributes']['name'] ?? null) === $name) {
                return $item;
            }
        }

        self::fail(sprintf('Product with name "%s" was not found in collection.', $name));
    }
}
