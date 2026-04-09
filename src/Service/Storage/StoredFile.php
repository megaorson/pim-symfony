<?php
declare(strict_types=1);

namespace App\Service\Storage;

final readonly class StoredFile
{
    public function __construct(
        public string $relativePath,
        public string $publicUrl,
        public string $fileName,
        public string $mimeType,
        public int $size,
    ) {
    }
}
