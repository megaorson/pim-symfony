<?php
declare(strict_types=1);

namespace App\Exception\Api;

final class ProductAttributeInUseException extends AbstractApiException
{
    public function __construct(string $message, int|string|null $id = null, ?string $code = null)
    {
        parent::__construct(
            message: $message,
            status: 409,
            type: 'product_attribute_in_use',
            context: [
                'id' => $id,
                'code' => $code,
            ],
        );
    }
}
