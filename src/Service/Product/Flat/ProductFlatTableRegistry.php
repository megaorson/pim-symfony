<?php
declare(strict_types=1);

namespace App\Service\Product\Flat;

use App\Entity\ProductFlatRuntimeState;
use App\Repository\ProductFlatRuntimeStateRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProductFlatTableRegistry
{
    public const TABLE_A = 'product_flat_a';
    public const TABLE_B = 'product_flat_b';

    public function __construct(
        private ProductFlatRuntimeStateRepository $stateRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getActiveTable(): string
    {
        return $this->getOrCreateState()->getActiveTable();
    }

    public function getStandbyTable(): string
    {
        return $this->getActiveTable() === self::TABLE_A
            ? self::TABLE_B
            : self::TABLE_A;
    }

    public function markBuilding(string $buildVersion): void
    {
        $state = $this->getOrCreateState();
        $state->setBuildStatus(ProductFlatRuntimeState::STATUS_BUILDING);
        $state->setBuildVersion($buildVersion);

        $this->entityManager->flush();
    }

    public function markFailed(string $buildVersion): void
    {
        $state = $this->getOrCreateState();
        $state->setBuildStatus(ProductFlatRuntimeState::STATUS_FAILED);
        $state->setBuildVersion($buildVersion);

        $this->entityManager->flush();
    }

    public function switchActiveTable(string $tableName, string $buildVersion): void
    {
        if (!in_array($tableName, [self::TABLE_A, self::TABLE_B], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported flat table "%s".', $tableName));
        }

        $state = $this->getOrCreateState();
        $state->setBuildStatus(ProductFlatRuntimeState::STATUS_SWITCHING);
        $this->entityManager->flush();

        $state->setActiveTable($tableName);
        $state->setBuildStatus(ProductFlatRuntimeState::STATUS_READY);
        $state->setBuildVersion($buildVersion);

        $this->entityManager->flush();
    }

    private function getOrCreateState(): ProductFlatRuntimeState
    {
        $state = $this->stateRepository->getState();

        if ($state instanceof ProductFlatRuntimeState) {
            return $state;
        }

        $state = new ProductFlatRuntimeState();
        $state->setId(1);
        $state->setActiveTable(self::TABLE_A);
        $state->setBuildStatus(ProductFlatRuntimeState::STATUS_READY);
        $state->setBuildVersion('auto-created');

        $this->entityManager->persist($state);
        $this->entityManager->flush();

        return $state;
    }
}
