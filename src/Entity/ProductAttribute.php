<?php
declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use App\Repository\ProductAttributeRepository;
use App\Subscriber\TimestampSubscriber;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductAttributeRepository::class)]
#[ORM\EntityListeners([TimestampSubscriber::class])]
class ProductAttribute implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $code;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isRequired = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isFilterable = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isSortable = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isSelectable = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): static
    {
        $this->isRequired = $isRequired;

        return $this;
    }

    public function isFilterable(): bool
    {
        return $this->isFilterable;
    }

    public function setIsFilterable(bool $isFilterable): static
    {
        $this->isFilterable = $isFilterable;

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->isSortable;
    }

    public function setIsSortable(bool $isSortable): static
    {
        $this->isSortable = $isSortable;

        return $this;
    }

    public function isSelectable(): bool
    {
        return $this->isSelectable;
    }

    public function setIsSelectable(bool $isSelectable): static
    {
        $this->isSelectable = $isSelectable;

        return $this;
    }
}
