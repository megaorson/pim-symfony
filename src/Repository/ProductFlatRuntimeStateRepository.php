<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProductFlatRuntimeState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class ProductFlatRuntimeStateRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($registry, ProductFlatRuntimeState::class);
    }

    public function getState(): ProductFlatRuntimeState
    {
        $state = $this->find(1);

        if ($state instanceof ProductFlatRuntimeState) {
            return $state;
        }

        $state = new ProductFlatRuntimeState();
        $state->setId(1);
        $state->setActiveTable('product_flat_a');
        $state->setBuildStatus(ProductFlatRuntimeState::STATUS_READY);
        $state->setBuildVersion('auto-created');

        $this->entityManager->persist($state);
        $this->entityManager->flush();

        return $state;
    }
}
