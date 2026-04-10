<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Product;

use App\Tests\Functional\Support\ProductApiTestCase;

final class ProductPatchTest extends ProductApiTestCase
{
    public function testPatchProductUpdatesExistingAttributes(): void
    {
        $this->createDefaultProductAttributes();

        $productId = $this->createProductIdThroughApi(
            sku: 'SKU-PATCH-001',
            attributes: [
                'name' => 'Old Product',
                'price' => 100.00,
                'qty' => 5,
            ],
        );

        $this->jsonPatch(
            '/api/products/' . $productId,
            $this->makeProductPatchPayload([
                'name' => 'Updated Product',
                'price' => 150.50,
            ]),
        );

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame($productId, $data['id']);

        $this->assertProductResponseContains($data, 'SKU-PATCH-001', [
            'name' => 'Updated Product',
            'price' => 150.50,
            'qty' => 5,
        ]);

        $product = $this->getProductById($productId);

        self::assertSame('SKU-PATCH-001', $product->getSku());

        $this->assertProductPersistedAttributes($product, [
            'name' => 'Updated Product',
            'price' => 150.50,
            'qty' => 5,
        ]);
    }

    public function testPatchProductAddsOptionalAttribute(): void
    {
        $this->createDefaultProductAttributes();

        $productId = $this->createProductIdThroughApi(
            sku: 'SKU-PATCH-002',
            attributes: [
                'name' => 'Product Without Qty',
                'price' => 200.00,
            ],
        );

        $this->jsonPatch(
            '/api/products/' . $productId,
            $this->makeProductPatchPayload([
                'qty' => 10,
            ]),
        );

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        $this->assertProductResponseContains($data, 'SKU-PATCH-002', [
            'name' => 'Product Without Qty',
            'price' => 200.00,
            'qty' => 10,
        ]);

        $product = $this->getProductById($productId);

        self::assertSame('SKU-PATCH-002', $product->getSku());

        $this->assertProductPersistedAttributes($product, [
            'name' => 'Product Without Qty',
            'price' => 200.00,
            'qty' => 10,
        ]);
    }

    public function testPatchProductKeepsUnchangedAttributes(): void
    {
        $this->createDefaultProductAttributes();

        $productId = $this->createProductIdThroughApi(
            sku: 'SKU-PATCH-003',
            attributes: [
                'name' => 'Stable Product',
                'price' => 300.00,
                'qty' => 7,
            ],
        );

        $this->jsonPatch(
            '/api/products/' . $productId,
            $this->makeProductPatchPayload([
                'name' => 'Renamed Product',
            ]),
        );

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        $this->assertProductResponseContains($data, 'SKU-PATCH-003', [
            'name' => 'Renamed Product',
            'price' => 300.00,
            'qty' => 7,
        ]);

        $product = $this->getProductById($productId);

        self::assertSame('SKU-PATCH-003', $product->getSku());

        $this->assertProductPersistedAttributes($product, [
            'name' => 'Renamed Product',
            'price' => 300.00,
            'qty' => 7,
        ]);
    }

    public function testPatchProductRemovesOptionalAttributeWithNull(): void
    {
        $this->createDefaultProductAttributes();

        $productId = $this->createProductIdThroughApi(
            sku: 'SKU-PATCH-004',
            attributes: [
                'name' => 'Nullable Product',
                'price' => 400.00,
                'qty' => 9,
            ],
        );

        $this->jsonPatch(
            '/api/products/' . $productId,
            $this->makeProductPatchPayload([
                'qty' => null,
            ]),
        );

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        $this->assertProductResponseContains($data, 'SKU-PATCH-004', [
            'name' => 'Nullable Product',
            'price' => 400.00,
        ]);

        $this->assertProductResponseDoesNotContainAttribute($data, 'qty');

        $product = $this->getProductById($productId);

        self::assertSame('SKU-PATCH-004', $product->getSku());

        $this->assertProductPersistedAttributes($product, [
            'name' => 'Nullable Product',
            'price' => 400.00,
        ]);

        $this->assertProductPersistedAttributeDoesNotExist($product, 'qty');
    }

    public function testPatchProductFailsWhenRequiredAttributeIsSetToNull(): void
    {
        $this->createDefaultProductAttributes();

        $productId = $this->createProductIdThroughApi(
            sku: 'SKU-PATCH-005',
            attributes: [
                'name' => 'Required Product',
                'price' => 500.00,
            ],
        );

        $this->jsonPatch(
            '/api/products/' . $productId,
            $this->makeProductPatchPayload([
                'name' => null,
            ]),
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testPatchProductFailsWhenUnknownAttributeIsProvided(): void
    {
        $this->createDefaultProductAttributes();

        $productId = $this->createProductIdThroughApi(
            sku: 'SKU-PATCH-006',
            attributes: [
                'name' => 'Unknown Patch Product',
                'price' => 600.00,
            ],
        );

        $this->jsonPatch(
            '/api/products/' . $productId,
            $this->makeProductPatchPayload([
                'unknown_field' => 'boom',
            ]),
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testPatchProductReturns404WhenProductDoesNotExist(): void
    {
        $this->createDefaultProductAttributes();

        $this->jsonPatch(
            '/api/products/999999',
            $this->makeProductPatchPayload([
                'name' => 'Ghost Product',
            ]),
        );

        self::assertResponseStatusCodeSame(404);
    }
}
