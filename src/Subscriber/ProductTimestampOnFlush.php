<?php
declare(strict_types=1);

namespace App\Subscriber;

use App\Entity\Product;
use App\Entity\ProductAttributeTypeInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

final class ProductTimestampOnFlush
{
    /** @var list<class-string<ProductAttributeTypeInterface>> */
    private array $valueEntityClasses;

    /**
     * @param array<string, class-string<ProductAttributeTypeInterface>> $valueEntityMap
     */
    public function __construct(array $valueEntityMap)
    {
        $this->valueEntityClasses = array_values($valueEntityMap);
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        /** @var array<int|string, Product> $productsToTouch */
        $productsToTouch = [];

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->collectProduct($entity, $productsToTouch);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->collectProduct($entity, $productsToTouch);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->collectProduct($entity, $productsToTouch);
        }

        if ($productsToTouch === []) {
            return;
        }

        $productMetadata = $em->getClassMetadata(Product::class);
        $now = new \DateTimeImmutable();

        foreach ($productsToTouch as $product) {
            if ($product->getCreatedAt() === null) {
                $product->setCreatedAt($now);
            }

            $product->setUpdatedAt($now);

            if ($uow->getEntityState($product) === UnitOfWork::STATE_MANAGED) {
                $uow->recomputeSingleEntityChangeSet($productMetadata, $product);
                continue;
            }

            $uow->computeChangeSet($productMetadata, $product);
        }
    }

    /**
     * @param array<int|string, Product> $productsToTouch
     */
    private function collectProduct(object $entity, array &$productsToTouch): void
    {
        if ($entity instanceof Product) {
            $productsToTouch[$this->getProductKey($entity)] = $entity;
            return;
        }

        if (!$entity instanceof ProductAttributeTypeInterface) {
            return;
        }

        if (!$this->isConfiguredValueEntity($entity)) {
            return;
        }

        $product = $entity->getProduct();

        if (!$product instanceof Product) {
            return;
        }

        $productsToTouch[$this->getProductKey($product)] = $product;
    }

    private function isConfiguredValueEntity(ProductAttributeTypeInterface $entity): bool
    {
        foreach ($this->valueEntityClasses as $entityClass) {
            if ($entity instanceof $entityClass) {
                return true;
            }
        }

        return false;
    }

    private function getProductKey(Product $product): int|string
    {
        return $product->getId() ?? spl_object_id($product);
    }
}
