<?php
declare(strict_types=1);

namespace App\Exception\Api;

final class DuplicateSkuException extends AbstractApiException
{
    public function __construct(string $message, string $sku)
    {
        parent::__construct(
            message: $message,
            status: 409,
            type: 'product_duplicate_sku',
            context: ['sku' => $sku],
        );
    }
}
