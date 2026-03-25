<?php
declare(strict_types=1);

namespace App\Form\Attribute;

use App\Entity\Product;
use App\Form\Attribute\Handler\AttributeFormHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class AttributeFormRegistry
{
    public function __construct(
        #[TaggedIterator('app.attribute_form_handler')]
        private iterable $handlers
    ) {
    }

    public function build($builder, $attributes, $product)
    : void {
        foreach ($attributes as $attribute) {
            foreach ($this->handlers as $handler) {
                if ($handler->supports($attribute->getType())) {
                    $handler->buildField($builder, $attribute, $product);
                    break;
                }
            }
        }
    }

    public function getHandler(string $type)
    : AttributeFormHandlerInterface {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($type)) {
                return $handler;
            }
        }

        throw new \RuntimeException(sprintf('No handler found for type "%s"', $type));
    }

    public function handleSubmit($builder, array $attributes, Product $product)
    : void {
        foreach ($attributes as $attribute) {
            $handler = $this->getHandler($attribute->getType());
            $handler->handleSubmit($builder, $attribute, $product);
        }
    }
}
