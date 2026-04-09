<?php
declare(strict_types=1);

namespace App\Tests\Functional\Support\Trait;

use App\Entity\ProductAttribute;

trait ProductTestFactoryTrait
{
    protected function createDefaultProductAttributes(): void
    {
        $this->createProductAttribute(
            code: 'name',
            type: 'text',
            name: 'Name',
            isRequired: true,
            isFilterable: true,
            isSortable: true,
        );

        $this->createProductAttribute(
            code: 'price',
            type: 'decimal',
            name: 'Price',
            isRequired: true,
            isFilterable: true,
            isSortable: true,
        );

        $this->createProductAttribute(
            code: 'qty',
            type: 'int',
            name: 'Quantity',
            isFilterable: true,
            isSortable: true,
        );
    }

    protected function createProductAttribute(
        string $code,
        string $type,
        string $name,
        bool $isRequired = false,
        bool $isFilterable = false,
        bool $isSortable = false,
        bool $isSelectable = true,
    ): ProductAttribute {
        return $this->createAttributeEntity(
            code: $code,
            name: $name,
            type: $type,
            isRequired: $isRequired,
            isFilterable: $isFilterable,
            isSortable: $isSortable,
            isSelectable: $isSelectable,
        );
    }

    protected function makeValidProductPayload(
        string $sku = 'SKU-001',
        array $attributes = []
    ): array {
        return [
            'sku' => $sku,
            'attributes' => array_replace([
                'name' => 'Test Product',
                'price' => 1234.56,
            ], $attributes),
        ];
    }

    protected function makeProductPatchPayload(array $attributes): array
    {
        return [
            'attributes' => $attributes,
        ];
    }

    protected function createProductThroughApi(
        string $sku = 'SKU-001',
        array $attributes = []
    ): array {
        $this->jsonPost('/api/products', $this->makeValidProductPayload(
            sku: $sku,
            attributes: $attributes,
        ));

        self::assertResponseStatusCodeSame(201);

        return $this->responseData();
    }

    protected function createProductThroughApiByArray(
        array $product = []
    ): array {
        $this->jsonPost('/api/products', $product);

        self::assertResponseStatusCodeSame(201);

        return $this->responseData();
    }

    protected function createProductIdThroughApi(
        string $sku = 'SKU-001',
        array $attributes = []
    ): int {
        $data = $this->createProductThroughApi(
            sku: $sku,
            attributes: $attributes,
        );

        return (int) $data['id'];
    }

    protected function getProductThroughApi(int $productId): array
    {
        $this->jsonGet('/api/products/' . $productId);

        self::assertResponseStatusCodeSame(200);

        return $this->responseData();
    }
}
