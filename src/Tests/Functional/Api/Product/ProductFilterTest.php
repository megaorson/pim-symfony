<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Product;

use App\Tests\Functional\Support\ProductApiTestCase;

final class ProductFilterTest extends ProductApiTestCase
{
    public function testFilterByTextEq(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-EQ-001',
            attributes: [
                'name' => 'Alpha',
                'price' => 100.00,
                'qty' => 1,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-EQ-002',
            attributes: [
                'name' => 'Beta',
                'price' => 200.00,
                'qty' => 2,
            ],
        );

        $this->jsonGet('/api/products?filter=name EQ \'Alpha\'');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(1, $data['total']);
        self::assertCount(1, $data['items']);

        $this->assertProductResponseContains($data['items'][0], 'SKU-FILTER-EQ-001', [
            'name' => 'Alpha',
            'price' => 100.00,
            'qty' => 1,
        ]);
    }

    public function testFilterByTextNe(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-NE-001',
            attributes: [
                'name' => 'Alpha',
                'price' => 100.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-NE-002',
            attributes: [
                'name' => 'Beta',
                'price' => 200.00,
            ],
        );

        $this->jsonGet('/api/products?filter=name NE \'Alpha\'');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(1, $data['total']);
        self::assertCount(1, $data['items']);

        $this->assertProductResponseContains($data['items'][0], 'SKU-FILTER-NE-002', [
            'name' => 'Beta',
            'price' => 200.00,
        ]);
    }

    public function testFilterByDecimalGt(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-GT-001',
            attributes: [
                'name' => 'Cheap',
                'price' => 500.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-GT-002',
            attributes: [
                'name' => 'Expensive',
                'price' => 1500.00,
            ],
        );

        $this->jsonGet('/api/products?filter=price GT 1000');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(1, $data['total']);
        self::assertCount(1, $data['items']);

        $this->assertProductResponseContains($data['items'][0], 'SKU-FILTER-GT-002', [
            'name' => 'Expensive',
            'price' => 1500.00,
        ]);
    }

    public function testFilterByDecimalGe(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-GE-001',
            attributes: [
                'name' => 'Low',
                'price' => 999.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-GE-002',
            attributes: [
                'name' => 'Border',
                'price' => 1000.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-GE-003',
            attributes: [
                'name' => 'High',
                'price' => 1500.00,
            ],
        );

        $this->jsonGet('/api/products?filter=price GE 1000');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(2, $data['total']);

        $border = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-GE-002');
        $high = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-GE-003');

        $this->assertProductResponseContains($border, 'SKU-FILTER-GE-002', [
            'name' => 'Border',
            'price' => 1000.00,
        ]);

        $this->assertProductResponseContains($high, 'SKU-FILTER-GE-003', [
            'name' => 'High',
            'price' => 1500.00,
        ]);
    }

    public function testFilterByDecimalLt(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-LT-001',
            attributes: [
                'name' => 'Low',
                'price' => 100.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-LT-002',
            attributes: [
                'name' => 'High',
                'price' => 1000.00,
            ],
        );

        $this->jsonGet('/api/products?filter=price LT 500');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(1, $data['total']);
        self::assertCount(1, $data['items']);

        $this->assertProductResponseContains($data['items'][0], 'SKU-FILTER-LT-001', [
            'name' => 'Low',
            'price' => 100.00,
        ]);
    }

    public function testFilterByDecimalLe(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-LE-001',
            attributes: [
                'name' => 'Below',
                'price' => 99.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-LE-002',
            attributes: [
                'name' => 'Equal',
                'price' => 100.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-LE-003',
            attributes: [
                'name' => 'Above',
                'price' => 101.00,
            ],
        );

        $this->jsonGet('/api/products?filter=price LE 100');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(2, $data['total']);

        $below = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-LE-001');
        $equal = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-LE-002');

        $this->assertProductResponseContains($below, 'SKU-FILTER-LE-001', [
            'name' => 'Below',
            'price' => 99.00,
        ]);

        $this->assertProductResponseContains($equal, 'SKU-FILTER-LE-002', [
            'name' => 'Equal',
            'price' => 100.00,
        ]);
    }

    public function testFilterByTextIn(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-IN-001',
            attributes: [
                'name' => 'One',
                'price' => 100.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-IN-002',
            attributes: [
                'name' => 'Two',
                'price' => 200.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-IN-003',
            attributes: [
                'name' => 'Three',
                'price' => 300.00,
            ],
        );

        $this->jsonGet('/api/products?filter=name IN (\'One\',\'Three\')');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(2, $data['total']);

        $one = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-IN-001');
        $three = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-IN-003');

        $this->assertProductResponseContains($one, 'SKU-FILTER-IN-001', [
            'name' => 'One',
            'price' => 100.00,
        ]);

        $this->assertProductResponseContains($three, 'SKU-FILTER-IN-003', [
            'name' => 'Three',
            'price' => 300.00,
        ]);
    }

    public function testFilterByNumericIn(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-IN-NUM-001',
            attributes: [
                'name' => 'Qty One',
                'price' => 100.00,
                'qty' => 1,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-IN-NUM-002',
            attributes: [
                'name' => 'Qty Two',
                'price' => 200.00,
                'qty' => 2,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-IN-NUM-003',
            attributes: [
                'name' => 'Qty Three',
                'price' => 300.00,
                'qty' => 3,
            ],
        );

        $this->jsonGet('/api/products?filter=qty IN (1,3)');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(2, $data['total']);

        $one = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-IN-NUM-001');
        $three = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-IN-NUM-003');

        $this->assertProductResponseContains($one, 'SKU-FILTER-IN-NUM-001', [
            'name' => 'Qty One',
            'price' => 100.00,
            'qty' => 1,
        ]);

        $this->assertProductResponseContains($three, 'SKU-FILTER-IN-NUM-003', [
            'name' => 'Qty Three',
            'price' => 300.00,
            'qty' => 3,
        ]);
    }

    public function testFilterByBegins(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-BEGINS-001',
            attributes: [
                'name' => 'test phone',
                'price' => 100.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-BEGINS-002',
            attributes: [
                'name' => 'test laptop',
                'price' => 200.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-BEGINS-003',
            attributes: [
                'name' => 'phone test',
                'price' => 300.00,
            ],
        );

        $this->jsonGet('/api/products?filter=name BEGINS \'test\'');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(2, $data['total']);

        $first = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-BEGINS-001');
        $second = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-BEGINS-002');

        $this->assertProductResponseContains($first, 'SKU-FILTER-BEGINS-001', [
            'name' => 'test phone',
            'price' => 100.00,
        ]);

        $this->assertProductResponseContains($second, 'SKU-FILTER-BEGINS-002', [
            'name' => 'test laptop',
            'price' => 200.00,
        ]);
    }

    public function testFilterBySkuEq(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-SKU-001',
            attributes: [
                'name' => 'First Product',
                'price' => 100.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-SKU-002',
            attributes: [
                'name' => 'Second Product',
                'price' => 200.00,
            ],
        );

        $this->jsonGet('/api/products?filter=sku EQ \'SKU-FILTER-SKU-002\'');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(1, $data['total']);
        self::assertCount(1, $data['items']);

        $this->assertProductResponseContains($data['items'][0], 'SKU-FILTER-SKU-002', [
            'name' => 'Second Product',
            'price' => 200.00,
        ]);
    }

    public function testFilterWithAnd(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-AND-001',
            attributes: [
                'name' => 'Phone Basic',
                'price' => 900.00,
                'qty' => 5,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-AND-002',
            attributes: [
                'name' => 'Phone Pro',
                'price' => 1500.00,
                'qty' => 3,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-AND-003',
            attributes: [
                'name' => 'Phone Zero',
                'price' => 1600.00,
                'qty' => 0,
            ],
        );

        $this->jsonGet('/api/products?filter=price GT 1000 AND qty GE 1');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(1, $data['total']);
        self::assertCount(1, $data['items']);

        $this->assertProductResponseContains($data['items'][0], 'SKU-FILTER-AND-002', [
            'name' => 'Phone Pro',
            'price' => 1500.00,
            'qty' => 3,
        ]);
    }

    public function testFilterWithOr(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-OR-001',
            attributes: [
                'name' => 'Alpha',
                'price' => 100.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-OR-002',
            attributes: [
                'name' => 'Beta',
                'price' => 200.00,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-OR-003',
            attributes: [
                'name' => 'Gamma',
                'price' => 300.00,
            ],
        );

        $this->jsonGet('/api/products?filter=name EQ \'Alpha\' OR name EQ \'Gamma\'');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(2, $data['total']);

        $alpha = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-OR-001');
        $gamma = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-OR-003');

        $this->assertProductResponseContains($alpha, 'SKU-FILTER-OR-001', [
            'name' => 'Alpha',
            'price' => 100.00,
        ]);

        $this->assertProductResponseContains($gamma, 'SKU-FILTER-OR-003', [
            'name' => 'Gamma',
            'price' => 300.00,
        ]);
    }

    public function testFilterWithParenthesesAndOr(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-PAREN-001',
            attributes: [
                'name' => 'Alpha',
                'price' => 1500.00,
                'qty' => 2,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-PAREN-002',
            attributes: [
                'name' => 'Beta',
                'price' => 1700.00,
                'qty' => 2,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-PAREN-003',
            attributes: [
                'name' => 'Gamma',
                'price' => 500.00,
                'qty' => 2,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-PAREN-004',
            attributes: [
                'name' => 'Alpha',
                'price' => 1500.00,
                'qty' => 0,
            ],
        );

        $this->jsonGet('/api/products?filter=(name EQ \'Alpha\' OR name EQ \'Beta\') AND qty GT 0');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(2, $data['total']);

        $alpha = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-PAREN-001');
        $beta = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-PAREN-002');

        $this->assertProductResponseContains($alpha, 'SKU-FILTER-PAREN-001', [
            'name' => 'Alpha',
            'price' => 1500.00,
            'qty' => 2,
        ]);

        $this->assertProductResponseContains($beta, 'SKU-FILTER-PAREN-002', [
            'name' => 'Beta',
            'price' => 1700.00,
            'qty' => 2,
        ]);
    }

    public function testFilterWithNestedParentheses(): void
    {
        $this->createDefaultProductAttributes();

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-NESTED-001',
            attributes: [
                'name' => 'Alpha',
                'price' => 100.00,
                'qty' => 1,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-NESTED-002',
            attributes: [
                'name' => 'Beta',
                'price' => 200.00,
                'qty' => 2,
            ],
        );

        $this->createProductThroughApi(
            sku: 'SKU-FILTER-NESTED-003',
            attributes: [
                'name' => 'Gamma',
                'price' => 300.00,
                'qty' => 0,
            ],
        );

        $this->jsonGet('/api/products?filter=((name EQ \'Alpha\' OR name EQ \'Beta\') AND qty GT 0) OR price GT 250');

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame(3, $data['total']);

        $alpha = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-NESTED-001');
        $beta = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-NESTED-002');
        $gamma = $this->findCollectionItemBySku($data['items'], 'SKU-FILTER-NESTED-003');

        $this->assertProductResponseContains($alpha, 'SKU-FILTER-NESTED-001', [
            'name' => 'Alpha',
            'price' => 100.00,
            'qty' => 1,
        ]);

        $this->assertProductResponseContains($beta, 'SKU-FILTER-NESTED-002', [
            'name' => 'Beta',
            'price' => 200.00,
            'qty' => 2,
        ]);

        $this->assertProductResponseContains($gamma, 'SKU-FILTER-NESTED-003', [
            'name' => 'Gamma',
            'price' => 300.00,
            'qty' => 0,
        ]);
    }

    public function testFilterReturns400ForUnknownField(): void
    {
        $this->createDefaultProductAttributes();

        $this->jsonGet('/api/products?filter=unknownField EQ 1');

        self::assertResponseStatusCodeSame(400);
    }

    public function testFilterReturns400ForInvalidSyntax(): void
    {
        $this->createDefaultProductAttributes();

        $this->jsonGet('/api/products?filter=price GT');

        self::assertResponseStatusCodeSame(400);
    }
}
