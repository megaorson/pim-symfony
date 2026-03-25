<?php
declare(strict_types=1);

namespace App\Form\Attribute\Handler;

use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use App\Entity\Product;
use App\Entity\ProductAttributeValueImage;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class ImageAttributeFormHandler extends AbstractAttributeFormHandler
{
    protected string $uploadDir;

    public function __construct(
        EntityManagerInterface            $em,
        protected ProductAttributeFactory $attributeFactory,
        string                            $uploadDir
    ) {
        parent::__construct($em, $attributeFactory);
        $this->uploadDir = $uploadDir;
    }

    public function supports(string $type)
    : bool {
        return $type === ProductAttributeValueImage::TYPE;
    }

    protected function getFormType()
    : string
    {
        return FileType::class;
    }

    public function handleSubmit(FormInterface $builder, ProductAttribute $attribute, Product $product)
    : void {
        parent::handleSubmit($builder, $attribute, $product);

        $existing = $this->findExisting($product, $attribute);
        $deleteField = $attribute->getCode() . '_delete';

        if ($builder->has($deleteField)) {
            $delete = $builder->get($deleteField)->getData();

            if ($delete && $existing && $existing->getValue()) {
                $oldPath = $this->uploadDir . '/' . $existing->getValue();

                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }

                $this->em->remove($existing);
                $this->em->flush();
            }
        }
    }

    public function buildField(FormInterface $builder, ProductAttribute $attribute, ?Product $product)
    : void {
        if ($product) {
            $existing = $this->findExisting($product, $attribute);
            $filePath = $existing ? $this->uploadDir . '/' . $existing->getValue() : null;
            $fileName = $existing?->getValue();

            $builder->add($attribute->getCode(), $this->getFormType(), [
                'label'    => $attribute->getName(),
                'required' => false,
                'mapped'   => false,
                'data'     => ($filePath && file_exists($filePath))
                    ? new File($filePath)
                    : null,

                'help'      => $fileName
                    ? '<img src="/uploads/images/' . $fileName . '" width="120">'
                    : null,
                'help_html' => true,
            ])->add($attribute->getCode() . '_delete', CheckboxType::class, [
                'label'    => 'Remove image',
                'required' => false,
                'mapped'   => false,
            ]);;

        }
    }

    protected function createEntity()
    {
        return $this->attributeFactory->create(ProductAttributeValueImage::TYPE);
    }

    protected function getCollection(Product $product)
    {
        return $product->getImageValues();
    }

    protected function processFiles($fileName, $value, $existing = null)
    : void {
        if ($existing && $existing->getValue()) {
            $oldPath = $this->uploadDir . '/' . $existing->getValue();

            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $value->move($this->uploadDir, $fileName);
    }

    protected function normalizeValue($value, $existing = null, Product $product = null)
    {
        if (!$value instanceof UploadedFile) {
            return $existing?->getValue();
        }
        $fileName = bin2hex(random_bytes(16)) . '.' . $value->guessExtension();

        $this->processFiles($fileName, $value, $existing);

        return $fileName;
    }
}
