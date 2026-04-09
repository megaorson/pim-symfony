<?php
declare(strict_types=1);

namespace App\Exception\Api;

final class InvalidProductImageMimeTypeException extends AbstractApiException
{
    /**
     * @param list<string> $allowedMimeTypes
     */
    public function __construct(
        string $message,
        ?string $mimeType = null,
        array $allowedMimeTypes = [],
    ) {
        parent::__construct(
            message: $message,
            status: 400,
            type: 'invalid_product_image_mime_type',
            context: [
                'mimeType' => $mimeType,
                'allowedMimeTypes' => $allowedMimeTypes,
            ],
        );
    }
}
