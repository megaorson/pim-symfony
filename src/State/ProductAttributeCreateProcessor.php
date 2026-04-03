<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Dto\ProductAttributeInput;
use App\ApiResource\Dto\ProductAttributeOutput;
use App\Entity\ProductAttribute;
use App\Exception\Api\EmptyProductAttributeCodeException;
use App\Exception\Api\EmptyProductAttributeNameException;
use App\Exception\Api\InvalidProductAttributeTypeException;
use App\Exception\Api\ProductAttributeCodeAlreadyExistsException;
use App\Repository\ProductAttributeRepository;
use App\Service\Eav\AttributeTypeRegistry;
use App\Service\ProductAttribute\Factory\ProductAttributeOutputFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductAttributeCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductAttributeRepository $productAttributeRepository,
        private readonly AttributeTypeRegistry $attributeTypeRegistry,
        private readonly ProductAttributeOutputFactory $productAttributeOutputFactory,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductAttributeOutput
    {
        /** @var ProductAttributeInput $data */
        $code = trim($data->code);
        $name = trim($data->name);
        $type = trim($data->type);

        if ($code === '') {
            throw new EmptyProductAttributeCodeException(
                $this->translator->trans('product_attribute.empty_code')
            );
        }

        if ($name === '') {
            throw new EmptyProductAttributeNameException(
                $this->translator->trans('product_attribute.empty_name')
            );
        }

        if ($this->productAttributeRepository->findOneBy(['code' => $code]) instanceof ProductAttribute) {
            throw new ProductAttributeCodeAlreadyExistsException(
                $this->translator->trans(
                    'product_attribute.code_already_exists',
                    ['%code%' => $code]
                ),
                $code
            );
        }

        $availableTypes = array_keys($this->attributeTypeRegistry->all());

        if (!in_array($type, $availableTypes, true)) {
            throw new InvalidProductAttributeTypeException(
                $this->translator->trans(
                    'product_attribute.invalid_type',
                    [
                        '%type%' => $type,
                        '%types%' => implode(', ', $availableTypes),
                    ]
                ),
                $type,
                $availableTypes
            );
        }

        $attribute = new ProductAttribute();
        $attribute->setCode($code);
        $attribute->setName($name);
        $attribute->setType($type);
        $attribute->setIsRequired($data->isRequired);
        $attribute->setIsFilterable($data->isFilterable);
        $attribute->setIsSortable($data->isSortable);

        $this->em->persist($attribute);
        $this->em->flush();

        return $this->productAttributeOutputFactory->create($attribute);
    }
}
