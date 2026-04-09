<?php
declare(strict_types=1);

namespace App\Subscriber;

use App\Entity\ProductAttributeValueImage;
use App\Service\Storage\FileStorageInterface;
use Doctrine\ORM\Event\PreRemoveEventArgs;

final readonly class ProductImageCleanupSubscriber
{
    public function __construct(
        private FileStorageInterface $fileStorage,
    ) {
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ProductAttributeValueImage) {
            return;
        }

        $this->fileStorage->delete($entity->getValue());
    }
}
