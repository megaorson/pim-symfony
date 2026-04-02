<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Exception\Api\InvalidAttributeValueException;
use App\Service\Eav\AttributeValueHandler\AttributeValueHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AttributeValueHandlerRegistry
{
    /**
     * @param iterable<AttributeValueHandlerInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function get(string $attributeType, string $attributeCode): AttributeValueHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($attributeType)) {
                return $handler;
            }
        }

        throw new InvalidAttributeValueException(
            $this->translator->trans(
                'product.attribute.invalid.unsupported_type',
                [
                    '%code%' => $attributeCode,
                    '%type%' => $attributeType,
                ]
            ),
            $attributeCode
        );
    }
}
