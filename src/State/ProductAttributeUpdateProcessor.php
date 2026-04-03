<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Dto\ProductAttributeOutput;
use App\ApiResource\Dto\ProductAttributePatchInput;
use App\Entity\ProductAttribute;
use App\Exception\Api\EmptyProductAttributeNameException;
use App\Exception\Api\ProductAttributeNotFoundException;
use App\Repository\ProductAttributeRepository;
use App\Service\ProductAttribute\Factory\ProductAttributeOutputFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductAttributeUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductAttributeRepository $productAttributeRepository,
        private readonly ProductAttributeOutputFactory $productAttributeOutputFactory,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductAttributeOutput
    {
        /** @var ProductAttributePatchInput $data */
        $attribute = $this->productAttributeRepository->find($uriVariables['id'] ?? null);

        if (!$attribute instanceof ProductAttribute) {
            throw new ProductAttributeNotFoundException(
                $this->translator->trans(
                    'product_attribute.not_found',
                    ['%id%' => (string) ($uriVariables['id'] ?? '')]
                ),
                $uriVariables['id'] ?? null
            );
        }

        if ($data->name !== null) {
            $name = trim($data->name);

            if ($name === '') {
                throw new EmptyProductAttributeNameException(
                    $this->translator->trans('product_attribute.empty_name')
                );
            }

            $attribute->setName($name);
        }

        if ($data->isRequired !== null) {
            $attribute->setIsRequired($data->isRequired);
        }

        if ($data->isFilterable !== null) {
            $attribute->setIsFilterable($data->isFilterable);
        }

        if ($data->isSortable !== null) {
            $attribute->setIsSortable($data->isSortable);
        }

        $this->em->flush();

        return $this->productAttributeOutputFactory->create($attribute);
    }
}
