<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Product;

use App\Tests\Functional\Support\ProductApiTestCase;

final class ProductGetItemTest extends ProductApiTestCase
{
    public function testGetProductReturnsExpectedAttributes(): void
    {
        $this->createDefaultProductAttributes();

        $productId = $this->createProductIdThroughApi(
            sku: 'SKU-GET-001',
            attributes: [
                'name' => 'Get Product',
                'price' => 1500.50,
            ],
        );

        $data = $this->getProductThroughApi($productId);

        self::assertSame($productId, $data['id']);

        $this->assertProductResponseContains($data, 'SKU-GET-001', [
            'name' => 'Get Product',
            'price' => 1500.50,
        ]);

        $this->assertProductResponseDoesNotContainAttribute($data, 'qty');

        $product = $this->getProductById($productId);
        self::assertSame('SKU-GET-001', $product->getSku());
    }

    public function testGetProductReturnsOptionalAttributeWhenItExists(): void
    {
        $this->createDefaultProductAttributes();

        $productId = $this->createProductIdThroughApi(
            sku: 'SKU-GET-002',
            attributes: [
                'name' => 'Get Product With Qty',
                'price' => 2200.00,
                'qty' => 7,
            ],
        );

        $data = $this->getProductThroughApi($productId);

        self::assertSame($productId, $data['id']);

        $this->assertProductResponseContains($data, 'SKU-GET-002', [
            'name' => 'Get Product With Qty',
            'price' => 2200.00,
            'qty' => 7,
        ]);
    }

    public function testGetProductReturns404WhenProductDoesNotExist(): void
    {
        $this->jsonGet('/api/products/999999');

        self::assertResponseStatusCodeSame(404);
    }
}
