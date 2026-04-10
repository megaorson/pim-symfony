<?php
declare(strict_types=1);

namespace App\Form\Attribute\Handler;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeValueImage;
use App\Service\Eav\AttributeTypeRegistry;
use App\Service\Product\ProductImageUploader;
use App\Service\Storage\FileStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ImageAttributeFormHandler extends AbstractAttributeFormHandler
{
    public function __construct(
        EntityManagerInterface $em,
        AttributeTypeRegistry $attributeTypeRegistry,
        private readonly TranslatorInterface $translator,
        private readonly string $uploadDir,
        private readonly ProductImageUploader $productImageUploader,
        private readonly FileStorageInterface $fileStorage,
    ) {
        parent::__construct($em, $attributeTypeRegistry);
    }

    protected function getFormType(): string
    {
        return FileType::class;
    }

    public function handleSubmit(FormInterface $builder, ProductAttribute $attribute, Product $product): void
    {
        $fieldName = $attribute->getCode();
        $deleteField = $fieldName . '_delete';
        $existing = $this->findExisting($product, $attribute);

        if ($builder->has($deleteField) && $builder->get($deleteField)->getData() === true) {
            $this->productImageUploader->delete($product, $attribute);

            return;
        }

        if (!$builder->has($fieldName)) {
            return;
        }

        $value = $builder->get($fieldName)->getData();

        if ($value instanceof UploadedFile) {
            $this->productImageUploader->upload($product, $attribute, $value);
        }
    }

    public function buildField(FormInterface $builder, ProductAttribute $attribute, ?Product $product): void
    {
        if (!$product) {
            return;
        }

        if (!$product->getId()) {
            return;
        }

        $existing = $this->findExisting($product, $attribute);
        $relativePath = $existing?->getValue();
        $absolutePath = $relativePath ? rtrim($this->uploadDir, '/') . '/' . ltrim($relativePath, '/') : null;
        $publicUrl = $this->fileStorage->publicUrl($relativePath);

        $builder
            ->add($attribute->getCode(), $this->getFormType(), [
                'label' => $attribute->getName(),
                'required' => $attribute->isRequired(),
                'mapped' => false,
                'data' => ($absolutePath && file_exists($absolutePath)) ? new File($absolutePath) : null,
                'help' => $publicUrl ? sprintf('<img src="%s" width="120">', $publicUrl) : null,
                'help_html' => true,
            ])
            ->add($attribute->getCode() . '_delete', CheckboxType::class, [
                'label' => $this->translator->trans('admin.product.image.remove'),
                'required' => false,
                'mapped' => false,
            ]);
    }

    protected function getCollection(Product $product)
    {
        return $product->getImageValues();
    }

    protected function getAttributeType(): string
    {
        return ProductAttributeValueImage::TYPE;
    }
}
