<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\State\ProcessorInterface;
use App\Dto\ProductInput;
use App\Entity\Product;
use App\Entity\ProductAttributeFactory;
use ApiPlatform\Metadata\Operation;
use App\Repository\ProductAttributeRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductProcessor extends AbstractProduct implements ProcessorInterface
{
    public function __construct(
        EntityManagerInterface $em,
        ProductRepository $productRepository,
        private ProductAttributeRepository $productAttributeRepository,
        private ProductAttributeFactory $productAttributeFactory
    ) {
        parent::__construct($em, $productRepository);
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Product
    {
        /** @var ProductInput $data */
        $product = new Product();
        $product->setSku($data->sku);

        foreach ($data->attributes as $code => $value) {
            $attribute = $this->productAttributeRepository->findOneBy(['code' => $code]);

            if (!$attribute) {
                throw new \Exception("Attribute {$code} not found");
            }

            $valueEntity = $this->productAttributeFactory->create($attribute->getType());
            $valueEntity->setValue($value);

            $valueEntity->setAttribute($attribute);
            $valueEntity->setProduct($product);

            $this->em->persist($valueEntity);
        }

        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }
}
