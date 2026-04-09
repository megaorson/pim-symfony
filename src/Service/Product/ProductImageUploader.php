<?php
declare(strict_types=1);

namespace App\Service\Product;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeValueImage;
use App\Service\Storage\FileStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ProductImageUploader
{
    public function __construct(
        private EntityManagerInterface $em,
        private FileStorageInterface $storage,
    ) {
    }

    public function upload(Product $product, ProductAttribute $attribute, UploadedFile $file): ProductAttributeValueImage
    {
        $existing = $this->findExisting($product, $attribute);

        $stored = $this->storage->storeProductImage(
            product: $product,
            attribute: $attribute,
            file: $file,
            oldRelativePath: $existing?->getValue()
        );

        $image = $existing ?? new ProductAttributeValueImage();

        $image
            ->setProduct($product)
            ->setAttribute($attribute)
            ->setValue($stored->relativePath);

        if (!$existing) {
            $this->em->persist($image);
        }

        $this->em->flush();

        return $image;
    }

    public function delete(Product $product, ProductAttribute $attribute): void
    {
        $existing = $this->findExisting($product, $attribute);

        if (!$existing) {
            return;
        }

        $this->storage->delete($existing->getValue());

        $this->em->remove($existing);
        $this->em->flush();
    }

    private function findExisting(Product $product, ProductAttribute $attribute): ?ProductAttributeValueImage
    {
        foreach ($product->getImageValues() as $value) {
            if ($value->getAttribute()?->getId() === $attribute->getId()) {
                return $value;
            }
        }

        return null;
    }
}
