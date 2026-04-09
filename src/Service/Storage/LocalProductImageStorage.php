<?php
declare(strict_types=1);

namespace App\Service\Storage;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class LocalProductImageStorage implements FileStorageInterface
{
    public function __construct(
        private string $projectDir,
        private string $uploadBaseDir = 'public/uploads/images',
        private string $uploadBasePublicPath = '/uploads/images',
    ) {
    }

    public function storeProductImage(
        Product $product,
        ProductAttribute $attribute,
        UploadedFile $file,
        ?string $oldRelativePath = null,
    ): StoredFile {
        $relativeDirectory = $this->buildRelativeDirectory($product, $attribute);
        $absoluteDirectory = $this->buildAbsolutePath($relativeDirectory);

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created.', $absoluteDirectory));
        }

        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        $size = $file->getSize() ?? 0;
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;

        $file->move($absoluteDirectory, $fileName);

        $relativePath = $relativeDirectory . '/' . $fileName;

        if ($oldRelativePath && $oldRelativePath !== $relativePath) {
            $this->delete($oldRelativePath);
        }

        return new StoredFile(
            relativePath: $relativePath,
            publicUrl: $this->publicUrl($relativePath) ?? '',
            fileName: $fileName,
            mimeType: $mimeType,
            size: $size,
        );
    }

    public function delete(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $absolutePath = $this->buildAbsolutePath($relativePath);

        if (is_file($absolutePath)) {
            unlink($absolutePath);

            $dir = dirname($absolutePath);

            if (is_dir($dir) && count(scandir($dir)) === 2) {
                rmdir($dir);
            }
        }
    }

    public function publicUrl(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }

        return rtrim($this->uploadBasePublicPath, '/') . '/' . ltrim($relativePath, '/');
    }

    private function buildRelativeDirectory(Product $product, ProductAttribute $attribute): string
    {
        $attributeSegment = sprintf(
            '%d_%s',
            $attribute->getId(),
            $this->sanitize($attribute->getCode())
        );

        return sprintf(
            'products/%d/%s',
            $product->getId(),
            $attributeSegment
        );
    }

    private function buildAbsolutePath(string $relativePath): string
    {
        return rtrim($this->projectDir, '/') . '/' . trim($this->uploadBaseDir, '/') . '/' . ltrim($relativePath, '/');
    }

    private function sanitize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9\-_]+/u', '_', $value) ?? 'attr';

        return trim($value, '_') ?: 'attr';
    }
}
