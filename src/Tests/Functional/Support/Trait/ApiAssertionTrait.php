<?php
declare(strict_types=1);

namespace App\Tests\Functional\Support\Trait;

trait ApiAssertionTrait
{
    protected function assertResponseIsJson(): void
    {
        $headers = $this->client->getResponse()->getHeaders(false);

        self::assertArrayHasKey('content-type', $headers);
        self::assertNotEmpty($headers['content-type']);
        self::assertStringContainsString('application/json', $headers['content-type'][0]);
    }

    protected function assertResponseIsProblemJson(): void
    {
        $headers = $this->client->getResponse()->getHeaders(false);

        self::assertArrayHasKey('content-type', $headers);
        self::assertNotEmpty($headers['content-type']);

        self::assertStringContainsString(
            'application/problem+json',
            $headers['content-type'][0]
        );
    }

    protected function assertJsonFieldSame(string $field, mixed $expected): void
    {
        $data = $this->responseData();

        self::assertArrayHasKey($field, $data);
        self::assertSame($expected, $data[$field]);
    }

    protected function assertJsonHasField(string $field): void
    {
        $data = $this->responseData();

        self::assertArrayHasKey($field, $data);
    }

    protected function assertViolationFor(string $field): void
    {
        $data = $this->responseData(false);

        self::assertArrayHasKey('violations', $data, 'Response does not contain "violations" key.');

        $propertyPaths = array_column($data['violations'], 'propertyPath');

        self::assertContains($field, $propertyPaths, sprintf(
            'Expected violation for field "%s". Actual fields: %s',
            $field,
            implode(', ', $propertyPaths),
        ));
    }

    protected function assertViolationMessageFor(string $field, string $expectedMessage): void
    {
        $data = $this->responseData(false);

        self::assertArrayHasKey('violations', $data, 'Response does not contain "violations" key.');

        foreach ($data['violations'] as $violation) {
            if (($violation['propertyPath'] ?? null) === $field) {
                self::assertSame($expectedMessage, $violation['message'] ?? null);
                return;
            }
        }

        self::fail(sprintf('No violation found for field "%s".', $field));
    }

    protected function assertViolationsCount(int $expectedCount): void
    {
        $data = $this->responseData(false);

        self::assertArrayHasKey('violations', $data);
        self::assertCount($expectedCount, $data['violations']);
    }

    protected function assertCollectionItemsCount(int $expectedCount): void
    {
        $data = $this->responseData();

        self::assertArrayHasKey('items', $data);
        self::assertIsArray($data['items']);
        self::assertCount($expectedCount, $data['items']);
    }

    protected function assertCollectionTotalItems(int $expected): void
    {
        $data = $this->responseData();

        self::assertArrayHasKey('totalItems', $data);
        self::assertSame($expected, $data['totalItems']);
    }

    protected function assertItemHasId(): void
    {
        $data = $this->responseData();

        self::assertArrayHasKey('id', $data);
        self::assertNotNull($data['id']);
    }

    protected function assertErrorMessageSame(string $expectedMessage): void
    {
        $data = $this->responseData(false);

        self::assertArrayHasKey('message', $data);
        self::assertSame($expectedMessage, $data['message']);
    }
}
