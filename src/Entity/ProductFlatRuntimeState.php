<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductFlatRuntimeStateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductFlatRuntimeStateRepository::class)]
#[ORM\Table(name: 'product_flat_runtime_state')]
class ProductFlatRuntimeState
{
    public const STATUS_READY = 'ready';
    public const STATUS_BUILDING = 'building';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SWITCHING = 'switching';

    #[ORM\Id]
    #[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
    private int $id = 1;

    #[ORM\Column(name: 'active_table', type: 'string', length: 32)]
    private string $activeTable = 'product_flat_a';

    #[ORM\Column(name: 'build_status', type: 'string', length: 32)]
    private string $buildStatus = self::STATUS_READY;

    #[ORM\Column(name: 'build_version', type: 'string', length: 64, nullable: true)]
    private ?string $buildVersion = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
        $this->touch();
    }

    public function getActiveTable(): string
    {
        return $this->activeTable;
    }

    public function setActiveTable(string $activeTable): void
    {
        $this->activeTable = $activeTable;
        $this->touch();
    }

    public function getBuildStatus(): string
    {
        return $this->buildStatus;
    }

    public function setBuildStatus(string $buildStatus): void
    {
        $this->buildStatus = $buildStatus;
        $this->touch();
    }

    public function getBuildVersion(): ?string
    {
        return $this->buildVersion;
    }

    public function setBuildVersion(?string $buildVersion): void
    {
        $this->buildVersion = $buildVersion;
        $this->touch();
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
