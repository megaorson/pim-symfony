<?php
declare(strict_types=1);

namespace App\Subscriber;

use App\Entity\Contracts\TimestampableInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;

final class TimestampSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist($entity): void
    {
        if (!$entity instanceof TimestampableInterface) {
            return;
        }

        $now = new \DateTimeImmutable();

        $entity->setCreatedAt($now);
        $entity->setUpdatedAt($now);
    }

    public function preUpdate($entity): void
    {
        if (!$entity instanceof TimestampableInterface) {
            return;
        }

        $entity->setUpdatedAt(new \DateTimeImmutable());
    }
}
