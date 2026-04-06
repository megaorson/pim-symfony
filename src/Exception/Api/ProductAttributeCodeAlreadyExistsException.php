<?php
declare(strict_types=1);

namespace App\Exception\Api;

final class ProductAttributeCodeAlreadyExistsException extends AbstractApiException
{
    public function __construct(string $message, string $code)
    {
        parent::__construct(
            message: $message,
            status: 422,
            type: 'product_attribute_code_already_exists',
            context: ['code' => $code],
        );
    }
}
