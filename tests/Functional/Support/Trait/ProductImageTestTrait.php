<?php

namespace App\Tests\Functional\Support\Trait;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\ResponseInterface;

trait ProductImageTestTrait
{
    protected string $fixturesDir;

    protected string $projectDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $this->fixturesDir = $this->projectDir . '/tests/Fixtures/files';

        $this->cleanupUploads();
    }

    protected function tearDown(): void
    {
        $this->cleanupUploads();
        parent::tearDown();
    }

    protected function uploadProductImage(int $productId, string $attributeCode, UploadedFile $file): ResponseInterface
    {
        return $this->client->request('POST', '/api/products/' . $productId . '/images', [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'extra' => [
                'parameters' => [
                    'attributeCode' => $attributeCode,
                ],
                'files' => [
                    'file' => $file,
                ],
            ],
        ]);
    }

    protected function createUploadedFile(string $name, string $mime): UploadedFile
    {
        $source = $this->fixturesDir . '/' . $name;

        self::assertFileExists(
            $source,
            sprintf('Fixture file not found: %s', $source)
        );

        $tmp = sys_get_temp_dir() . '/' . uniqid('upload_', true) . '_' . $name;

        copy($source, $tmp);

        self::assertFileExists(
            $tmp,
            sprintf('Temp file was not created: %s', $tmp)
        );

        return new UploadedFile(
            $tmp,
            $name,
            $mime,
            null,
            true
        );
    }

    protected function cleanupUploads(): void
    {
        $dir = $this->projectDir . '/public/uploads/images/products';

        if (!is_dir($dir)) {
            return;
        }

        $this->removeDir($dir);
    }

    protected function removeDir(string $dir): void
    {
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->removeDir($path);
                continue;
            }

            unlink($path);
        }

        rmdir($dir);
    }
}
