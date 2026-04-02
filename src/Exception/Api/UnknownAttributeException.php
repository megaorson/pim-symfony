<?php
declare(strict_types=1);

namespace App\Exception\Api;

final class UnknownAttributeException extends AbstractApiException
{
    public function __construct(string $message, string $code)
    {
        parent::__construct(
            message: $message,
            status: 400,
            type: 'product_attribute_not_found',
            context: ['code' => $code],
        );
    }
}
