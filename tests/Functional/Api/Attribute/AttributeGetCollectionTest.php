<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Attribute;

use App\Tests\Functional\Support\AttributeApiTestCase;

final class AttributeGetCollectionTest extends AttributeApiTestCase
{
    public function testGetEmptyCollection(): void
    {
        $this->jsonGet('/api/attributes');

        self::assertResponseStatusCodeSame(200);
        $this->assertResponseIsJson();

        $data = $this->responseData();

        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('totalItems', $data);
        self::assertArrayHasKey('page', $data);
        self::assertArrayHasKey('limit', $data);
        self::assertArrayHasKey('offset', $data);

        self::assertSame([], $data['items']);
        self::assertSame(0, $data['totalItems']);
        self::assertSame(1, $data['page']);
        self::assertSame(10, $data['limit']);
        self::assertSame(0, $data['offset']);
    }

    public function testGetCollectionReturnsCreatedAttributes(): void
    {
        $this->createAttributeEntity(code: 'name', name: 'Name');
        $this->createAttributeEntity(code: 'price', name: 'Price', type: 'decimal');

        $this->jsonGet('/api/attributes');

        self::assertResponseStatusCodeSame(200);
        $this->assertResponseIsJson();

        $data = $this->responseData();

        self::assertCount(2, $data['items']);
        self::assertSame(2, $data['totalItems']);
        self::assertSame(1, $data['page']);
        self::assertSame(10, $data['limit']);
        self::assertSame(0, $data['offset']);

        $codes = array_column($data['items'], 'code');

        self::assertContains('name', $codes);
        self::assertContains('price', $codes);
    }

    public function testGetCollectionSupportsPageAndLimit(): void
    {
        $this->createAttributeEntity(code: 'attr_1', name: 'Attr 1');
        $this->createAttributeEntity(code: 'attr_2', name: 'Attr 2');
        $this->createAttributeEntity(code: 'attr_3', name: 'Attr 3');

        $this->jsonGet('/api/attributes?page=2&limit=1');

        self::assertResponseStatusCodeSame(200);
        $this->assertResponseIsJson();

        $data = $this->responseData();

        self::assertCount(1, $data['items']);
        self::assertSame(3, $data['totalItems']);
        self::assertSame(2, $data['page']);
        self::assertSame(1, $data['limit']);
        self::assertSame(1, $data['offset']);
        self::assertSame('attr_2', $data['items'][0]['code']);
    }

    public function testGetCollectionSupportsSecondPageWithTwoItemsPerPage(): void
    {
        $this->createAttributeEntity(code: 'attr_1', name: 'Attr 1');
        $this->createAttributeEntity(code: 'attr_2', name: 'Attr 2');
        $this->createAttributeEntity(code: 'attr_3', name: 'Attr 3');
        $this->createAttributeEntity(code: 'attr_4', name: 'Attr 4');

        $this->jsonGet('/api/attributes?page=2&limit=2');

        self::assertResponseStatusCodeSame(200);
        $this->assertResponseIsJson();

        $data = $this->responseData();

        self::assertCount(2, $data['items']);
        self::assertSame(4, $data['totalItems']);
        self::assertSame(2, $data['page']);
        self::assertSame(2, $data['limit']);
        self::assertSame(2, $data['offset']);

        self::assertSame('attr_3', $data['items'][0]['code']);
        self::assertSame('attr_4', $data['items'][1]['code']);
    }
}
