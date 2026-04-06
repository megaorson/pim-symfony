<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Attribute;

use App\Tests\Functional\Support\ApiTestCase;

final class AttributeGetItemTest extends ApiTestCase
{
    public function testGetExistingAttribute(): void
    {
        $attribute = $this->createAttributeEntity(
            code: 'name',
            name: 'Name',
        );

        $this->jsonGet('/api/attributes/' . $attribute->getId());

        self::assertResponseStatusCodeSame(200);
        $this->assertResponseIsJson();

        $data = $this->responseData();

        self::assertSame($attribute->getId(), $data['id']);
        self::assertSame('name', $data['code']);
        self::assertSame('Name', $data['name']);
        self::assertSame('text', $data['type']);
        self::assertFalse($data['isRequired']);
        self::assertFalse($data['isFilterable']);
        self::assertFalse($data['isSortable']);
        self::assertTrue($data['isSelectable']);
    }

    public function testGetMissingAttributeReturns404(): void
    {
        $this->jsonGet('/api/attributes/999999');

        self::assertResponseStatusCodeSame(404);
    }
}
