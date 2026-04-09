<?php
declare(strict_types=1);

namespace App\Service\Storage;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileStorageInterface
{
    public function storeProductImage(
        Product $product,
        ProductAttribute $attribute,
        UploadedFile $file,
        ?string $oldRelativePath = null,
    ): StoredFile;

    public function delete(?string $relativePath): void;

    public function publicUrl(?string $relativePath): ?string;
}
