<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Product;

use App\Tests\Functional\Support\ProductApiTestCase;

final class ProductCreateTest extends ProductApiTestCase
{
    public function testCreateProductWithMinimalRequiredAttributes(): void
    {
        $this->createDefaultProductAttributes();

        $data = $this->createProductThroughApi(
            attributes: [
                'name' => 'Test Product',
                'price' => 1234.56,
            ],
        );

        $this->assertProductResponseContains($data, 'SKU-001', [
            'name' => 'Test Product',
            'price' => 1234.56,
        ]);

        $this->assertProductResponseDoesNotContainAttribute($data, 'qty');

        $product = $this->getProductById((int) $data['id']);

        self::assertSame('SKU-001', $product->getSku());
    }

    public function testCreateProductWithOptionalAttribute(): void
    {
        $this->createDefaultProductAttributes();

        $data = $this->createProductThroughApi(
            sku: 'SKU-002',
            attributes: [
                'name' => 'Second Product',
                'price' => 999.99,
                'qty' => 5,
            ],
        );

        $this->assertProductResponseContains($data, 'SKU-002', [
            'name' => 'Second Product',
            'price' => 999.99,
            'qty' => 5,
        ]);

        $product = $this->getProductById((int) $data['id']);

        self::assertSame('SKU-002', $product->getSku());
    }

    public function testCreateProductFailsWhenRequiredAttributeIsMissing(): void
    {
        $this->createDefaultProductAttributes();

        $payload = [
            'sku' => 'SKU-003',
            'attributes' => [
                'name' => 'Broken Product',
            ],
        ];

        $this->jsonPost('/api/products', $payload);

        self::assertResponseStatusCodeSame(422);
    }

    public function testCreateProductFailsWhenSkuIsMissing(): void
    {
        $this->createDefaultProductAttributes();

        $payload = [
            'attributes' => [
                'name' => 'Broken Product',
                'sku' => 'SKU-003',
            ],
        ];

        $this->jsonPost('/api/products', $payload);

        self::assertResponseStatusCodeSame(422);

        $this->assertViolationFor('sku');
    }

    public function testCreateProductFailsWhenUnknownAttributeIsProvided(): void
    {
        $this->createDefaultProductAttributes();

        $payload = $this->makeValidProductPayload(
            sku: 'SKU-004',
            attributes: [
                'name' => 'Unknown Attr Product',
                'price' => 100.50,
                'unknown_field' => 'boom',
            ],
        );

        $this->jsonPost('/api/products', $payload);

        self::assertResponseStatusCodeSame(400);
    }
}
