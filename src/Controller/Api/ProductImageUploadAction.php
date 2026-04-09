<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\ProductAttributeValueImage;
use App\Exception\Api\EmptyProductAttributeCodeException;
use App\Exception\Api\InvalidProductAttributeTypeException;
use App\Exception\Api\InvalidProductImageMimeTypeException;
use App\Exception\Api\ProductAttributeNotFoundException;
use App\Exception\Api\ProductImageFileRequiredException;
use App\Exception\Api\ProductNotFoundException;
use App\Repository\ProductAttributeRepository;
use App\Repository\ProductRepository;
use App\Service\Eav\AttributeTypeRegistry;
use App\Service\Product\ProductImageUploader;
use App\Service\Storage\FileStorageInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsController]
final readonly class ProductImageUploadAction
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductAttributeRepository $attributeRepository,
        private ProductImageUploader $uploader,
        private FileStorageInterface $storage,
        private TranslatorInterface $translator,
        private AttributeTypeRegistry $attributeTypeRegistry,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if ($product === null) {
            throw new ProductNotFoundException(
                message: $this->translator->trans('product.not_found'),
                id: $id,
            );
        }

        $attributeCode = $request->request->get('attributeCode');

        if (!is_string($attributeCode) || trim($attributeCode) === '') {
            throw new EmptyProductAttributeCodeException(
                $this->translator->trans('product_image.attribute_code_required')
            );
        }

        $attribute = $this->attributeRepository->findOneBy(['code' => $attributeCode]);

        if ($attribute === null) {
            throw new ProductAttributeNotFoundException(
                message: $this->translator->trans('product_attribute.not_found', [
                    '%code%' => $attributeCode,
                ]),
                id: $attributeCode,
            );
        }

        $availableTypes = array_keys($this->attributeTypeRegistry->all());

        if ($attribute->getType() !== ProductAttributeValueImage::TYPE) {
            throw new InvalidProductAttributeTypeException(
                message: $this->translator->trans('product_image.invalid_attribute_type'),
                type: $attribute->getType(),
                allowedTypes: $availableTypes,
                attributeCode: $attributeCode,
                expectedType: ProductAttributeValueImage::TYPE,
                actualType: $attribute->getType(),
            );
        }

        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile) {
            throw new ProductImageFileRequiredException(
                $this->translator->trans('product_image.file_required')
            );
        }

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        $mimeType = $file->getMimeType();

        if ($mimeType === null || !in_array($mimeType, $allowedMimeTypes, true)) {
            throw new InvalidProductImageMimeTypeException(
                message: $this->translator->trans('product_image.invalid_mime_type', [
                    '%mimeType%' => $mimeType ?? 'unknown',
                    '%allowedMimeTypes%' => implode(', ', $allowedMimeTypes),
                ]),
                mimeType: $mimeType,
                allowedMimeTypes: $allowedMimeTypes,
            );
        }

        $imageValue = $this->uploader->upload($product, $attribute, $file);

        return new JsonResponse([
            'message' => $this->translator->trans('product_image.upload_success'),
            'productId' => $product->getId(),
            'attributeCode' => $attribute->getCode(),
            'image' => [
                'path' => $imageValue->getValue(),
                'url' => $this->storage->publicUrl($imageValue->getValue()),
            ],
        ], JsonResponse::HTTP_CREATED);
    }
}
