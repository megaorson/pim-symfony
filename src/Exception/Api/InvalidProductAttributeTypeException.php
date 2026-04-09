<?php
declare(strict_types=1);

namespace App\Exception\Api;

final class InvalidProductAttributeTypeException extends AbstractApiException
{
    public function __construct(
        string  $message,
        ?string $type = null,
        array $allowedTypes = [],
        ?string $attributeCode = null,
        ?string $expectedType = null,
        ?string $actualType = null,
    ) {
        parent::__construct(
            message: $message,
            status : 400,
            type   : 'invalid_product_attribute_type',
            context: [
                'attributeCode' => $attributeCode,
                'expectedType'  => $expectedType,
                'actualType'    => $actualType,
                'type'          => $type,
                'allowedTypes'  => array_values($allowedTypes),
            ],
        );
    }
}
