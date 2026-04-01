<?php
declare(strict_types=1);

namespace App\Exception\Api;

abstract class AbstractApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        protected int $status = 400,
        protected ?string $type = null,
        protected array $context = []
    ) {
        parent::__construct($message);
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
