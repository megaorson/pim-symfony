<?php
declare(strict_types=1);

namespace App\Exception\Api;

final class EmptyProductAttributeCodeException extends AbstractApiException
{
    public function __construct(string $message)
    {
        parent::__construct(
            message: $message,
            status: 400,
            type: 'empty_product_attribute_code',
            context: [],
        );
    }
}
