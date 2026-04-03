<?php
declare(strict_types=1);

namespace App\Exception\Api;

final class InvalidProductAttributeTypeException extends AbstractApiException
{
    public function __construct(string $message, string $type, array $allowedTypes)
    {
        parent::__construct(
            message: $message,
            status: 400,
            type: 'invalid_product_attribute_type',
            context: [
                'type' => $type,
                'allowedTypes' => array_values($allowedTypes),
            ],
        );
    }
}
