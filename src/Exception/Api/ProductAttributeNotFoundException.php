<?php
declare(strict_types=1);

namespace App\Exception\Api;

final class ProductAttributeNotFoundException extends AbstractApiException
{
    public function __construct(string $message, int|string|null $id = null)
    {
        parent::__construct(
            message: $message,
            status: 404,
            type: 'product_attribute_not_found',
            context: ['id' => $id],
        );
    }
}
