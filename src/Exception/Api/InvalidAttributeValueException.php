<?php
declare(strict_types=1);

namespace App\Exception\Api;

final class InvalidAttributeValueException extends AbstractApiException
{
    public function __construct(string $message, string $code)
    {
        parent::__construct(
            message: $message,
            status: 400,
            type: 'product_invalid_attribute_value',
            context: ['code' => $code],
        );
    }
}
