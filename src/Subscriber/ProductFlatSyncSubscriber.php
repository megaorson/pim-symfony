<?php
declare(strict_types=1);

namespace App\Subscriber;

use App\Entity\Product;
use App\Entity\ProductAttributeValueDecimal;
use App\Entity\ProductAttributeValueImage;
use App\Entity\ProductAttributeValueInt;
use App\Entity\ProductAttributeValueText;
use App\Service\Product\Flat\ProductFlatUpdaterInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Event\PostPersistEventArgs;

final class ProductFlatSyncSubscriber implements EventSubscriber
{
    /**
     * @var array<int, true>
     */
    private array $productIdsToReindex = [];

    /**
     * @var array<int, true>
     */
    private array $productIdsToDelete = [];

    private bool $isProcessing = false;

    public function __construct(
        private readonly ProductFlatUpdaterInterface $productFlatUpdater,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
            Events::postFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $this->collectInsertedEntities($uow);
        $this->collectUpdatedEntities($uow);
        $this->collectDeletedEntities($uow);
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Product) {
            return;
        }

        $productId = $entity->getId();

        if ($productId === null) {
            return;
        }

        $this->productIdsToReindex[$productId] = true;
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->isProcessing) {
            return;
        }

        if ($this->productIdsToReindex === [] && $this->productIdsToDelete === []) {
            return;
        }

        $this->isProcessing = true;

        try {
            $deleteIds = array_keys($this->productIdsToDelete);
            $reindexIds = array_keys($this->productIdsToReindex);

            $this->productIdsToDelete = [];
            $this->productIdsToReindex = [];

            foreach ($deleteIds as $productId) {
                $this->productFlatUpdater->deleteProduct($productId);
            }

            foreach ($reindexIds as $productId) {
                if (in_array($productId, $deleteIds, true)) {
                    continue;
                }

                $this->productFlatUpdater->updateProduct($productId);
            }
        } finally {
            $this->isProcessing = false;
        }
    }

    private function collectInsertedEntities(UnitOfWork $uow): void
    {
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->collectEntity($entity, false);
        }
    }

    private function collectUpdatedEntities(UnitOfWork $uow): void
    {
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->collectEntity($entity, false);
        }
    }

    private function collectDeletedEntities(UnitOfWork $uow): void
    {
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->collectEntity($entity, true);
        }
    }

    private function collectEntity(object $entity, bool $isDelete): void
    {
        if ($entity instanceof Product) {
            $productId = $entity->getId();

            if ($productId === null) {
                return;
            }

            if ($isDelete) {
                $this->productIdsToDelete[$productId] = true;
            } else {
                $this->productIdsToReindex[$productId] = true;
            }

            return;
        }

        $productId = $this->extractProductIdFromValueEntity($entity);

        if ($productId === null) {
            return;
        }

        $this->productIdsToReindex[$productId] = true;
    }

    private function extractProductIdFromValueEntity(object $entity): ?int
    {
        if (
            !$entity instanceof ProductAttributeValueText
            && !$entity instanceof ProductAttributeValueInt
            && !$entity instanceof ProductAttributeValueDecimal
            && !$entity instanceof ProductAttributeValueImage
        ) {
            return null;
        }

        $product = $entity->getProduct();
        $productId = $product?->getId();

        return $productId !== null ? (int) $productId : null;
    }
}
