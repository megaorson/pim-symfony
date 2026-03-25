<?php
declare(strict_types=1);

namespace App\Form\Attribute\Handler;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;

abstract class AbstractAttributeFormHandler implements AttributeFormHandlerInterface
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected ProductAttributeFactory $attributeFactory
    ) {
    }

    abstract public function supports(string $type)
    : bool;

    abstract protected function getFormType()
    : string;

    abstract protected function createEntity();

    abstract protected function getCollection(Product $product);

    public function buildField(FormInterface $builder, ProductAttribute $attribute, ?Product $product)
    : void {
        if ($product) {
            $existing = $this->findExisting($product, $attribute);
            $builder->add($attribute->getCode(), $this->getFormType(), [
                'label'    => $attribute->getName(),
                'required' => false,
                'mapped'   => false,
                'data'     => $existing?->getValue(),
            ]);
        }
    }

    public function handleSubmit(FormInterface $builder, ProductAttribute $attribute, Product $product): void
    {
        $fieldName = $attribute->getCode();

        if (!$builder->has($fieldName)) {
            return;
        }

        $value = $builder->get($fieldName)->getData();

        $existing = $this->findExisting($product, $attribute);

        $normalized = $this->normalizeValue($value, $existing, $product);

        if ($normalized === null) {
            return;
        }

        if (!$existing) {
            $existing = $this->createEntity();
            $existing->setProduct($product);
            $existing->setAttribute($attribute);

            $this->getCollection($product)->add($existing);
        }

        $existing->setValue($normalized);
    }

    protected function findExisting(Product $product, ProductAttribute $attribute)
    {
        foreach ($this->getCollection($product) as $val) {
            if ($val->getAttribute()->getId() === $attribute->getId()) {
                return $val;
            }
        }

        return null;
    }

    protected function normalizeValue($value, $existing = null, Product $product = null)
    {
        return $value;
    }
}
