<?php
declare(strict_types=1);

namespace App\Exception\Api;

final class MissingRequiredAttributesException extends AbstractApiException
{
    public function __construct(string $message, array $codes)
    {
        parent::__construct(
            message: $message,
            status: 400,
            type: 'product_missing_required_attributes',
            context: ['codes' => array_values($codes)],
        );
    }
}
