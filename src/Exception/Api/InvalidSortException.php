<?php
declare(strict_types=1);

namespace App\Exception\Api;

final class InvalidSortException extends AbstractApiException
{
    public function __construct(string $message, array $context = [])
    {
        parent::__construct(
            $message,
            400,
            'invalid_sort',
            $context
        );
    }
}
