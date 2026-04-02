<?php
declare(strict_types=1);

namespace App\Exception\Api;

abstract class AbstractApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $status = 400,
        private readonly ?string $type = null,
        private readonly array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
