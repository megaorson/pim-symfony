<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Attribute;

use App\Tests\Functional\Support\AttributeApiTestCase;

final class AttributeCreateTest extends AttributeApiTestCase
{
    public function testCreateTextAttribute(): void
    {
        $attributeData = $this->validAttributePayload([
            'code' => 'name',
            'name' => 'Name',
            'type' => 'text',
            'isRequired' => true,
            'isSelectable' => false,
            'isFilterable' => true,
            'isSortable' => true,
        ]);
        $this->jsonPost('/api/attributes', $attributeData);

        self::assertResponseStatusCodeSame(201);
        $this->assertResponseIsJson();
        $this->assertItemHasId();
        $this->assertJsonFieldSame('code', 'name');
        $this->assertJsonFieldSame('name', 'Name');
        $this->assertJsonFieldSame('type', 'text');

        $this->validateAttributeEntity($attributeData);
    }

    public function testCreateTextAttributeWithWrongCodeName(): void
    {
        $attributeData = [
            'code_test' => 'name',
            'name' => 'Name',
            'type' => 'text',
            'isRequired' => true,
            'isSelectable' => false,
            'isFilterable' => true,
            'isSortable' => true,
        ];
        $this->jsonPost('/api/attributes', $attributeData);

        self::assertResponseStatusCodeSame(422);
        $this->assertResponseIsProblemJson();
    }

    public function testCreateDecimalAttribute(): void
    {
        $attributeData = $this->validAttributePayload([
            'code' => 'price',
            'name' => 'Price',
            'type' => 'decimal',
            'isRequired' => true,
            'isSelectable' => true,
            'isFilterable' => false,
            'isSortable' => true,
        ]);
        $this->jsonPost('/api/attributes', $attributeData);

        self::assertResponseStatusCodeSame(201);
        $this->assertJsonFieldSame('code', 'price');
        $this->assertJsonFieldSame('type', 'decimal');

        $this->validateAttributeEntity($attributeData);
    }

    public function testCreateIntAttribute(): void
    {
        $attributeData = $this->validAttributePayload([
            'code' => 'qty',
            'name' => 'Quantity',
            'type' => 'int',
            'isRequired' => false,
            'isSelectable' => true,
            'isFilterable' => true,
            'isSortable' => true,
        ]);
        $this->jsonPost('/api/attributes', $attributeData);

        self::assertResponseStatusCodeSame(201);
        $this->assertJsonFieldSame('code', 'qty');
        $this->assertJsonFieldSame('type', 'int');

        $this->validateAttributeEntity($attributeData);
    }

    public function testCreateFailsWhenCodeIsMissing(): void
    {
        $payload = $this->validAttributePayload([
            'name' => 'Broken',
            'type' => 'text',
        ]);
        unset($payload['code']);

        $this->jsonPost('/api/attributes', $payload);

        self::assertResponseStatusCodeSame(422);
        $this->assertResponseIsProblemJson();
        $this->assertViolationFor('code');
    }

    public function testCreateFailsWhenNameIsMissing(): void
    {
        $payload = $this->validAttributePayload([
            'code' => 'broken_name',
            'type' => 'text',
        ]);
        unset($payload['name']);

        $this->jsonPost('/api/attributes', $payload);

        self::assertResponseStatusCodeSame(422);
        $this->assertResponseIsProblemJson();
        $this->assertViolationFor('name');
    }

    public function testCreateFailsWhenTypeIsMissing(): void
    {
        $payload = $this->validAttributePayload([
            'code' => 'broken_type',
            'name' => 'Broken Type',
        ]);
        unset($payload['type']);

        $this->jsonPost('/api/attributes', $payload);

        self::assertResponseStatusCodeSame(422);
        $this->assertResponseIsProblemJson();
        $this->assertViolationFor('type');
    }

    public function testCreateFailsWhenCodeAlreadyExists(): void
    {
        $this->createAttributeEntity(
            code: 'name',
            name: 'Name',
            isRequired: true,
            isFilterable: true,
            isSortable: true,
        );

        $this->jsonPost('/api/attributes', $this->validAttributePayload([
            'code' => 'name',
            'name' => 'Duplicate Name',
            'type' => 'text',
        ]));

        self::assertResponseStatusCodeSame(422);
    }

    public function testCreatePersistsFlagsAllSelected(): void
    {
        $this->jsonPost('/api/attributes', $this->validAttributePayload([
            'code' => 'brand',
            'name' => 'Brand',
            'type' => 'text',
            'isRequired' => true,
            'isFilterable' => true,
            'isSortable' => true,
            'isSelectable' => true,
        ]));

        self::assertResponseStatusCodeSame(201);

        $data = $this->responseData();

        self::assertTrue($data['isRequired']);
        self::assertTrue($data['isFilterable']);
        self::assertTrue($data['isSortable']);
        self::assertTrue($data['isSelectable']);
    }

    public function testCreatePersistsFlagsAllNotSelected(): void
    {
        $this->jsonPost('/api/attributes', $this->validAttributePayload([
            'code' => 'brand',
            'name' => 'Brand',
            'type' => 'text',
            'isRequired' => false,
            'isFilterable' => false,
            'isSortable' => false,
            'isSelectable' => false,
        ]));

        self::assertResponseStatusCodeSame(201);

        $data = $this->responseData();

        self::assertFalse($data['isRequired']);
        self::assertFalse($data['isFilterable']);
        self::assertFalse($data['isSortable']);
        self::assertFalse($data['isSelectable']);
    }
}
