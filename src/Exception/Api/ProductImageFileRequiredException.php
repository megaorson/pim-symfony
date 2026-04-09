<?php
declare(strict_types=1);

namespace App\Exception\Api;

final class ProductImageFileRequiredException extends AbstractApiException
{
    public function __construct(string $message)
    {
        parent::__construct(
            message: $message,
            status: 400,
            type: 'product_image_file_required',
            context: [],
        );
    }
}
