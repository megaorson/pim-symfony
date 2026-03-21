<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, Sku>
     */
    #[ORM\OneToMany(targetEntity: Sku::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $skus;

    public function __construct()
    {
        $this->skus = new ArrayCollection();
    }

    /**
     * Retrieves the ID.
     * @return int|null The ID value or null if not set.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retrieves the name.
     * @return string|null The name value or null if not set.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the name property.
     *
     * @param string $name The name to set.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Retrieves the description property.
     * @return string|null Returns the description or null if not set.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets the description property.
     *
     * @param string|null $description The description to set or null to unset.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Sku>
     */
    public function getSkus(): Collection
    {
        return $this->skus;
    }

    public function addSku(Sku $sku): static
    {
        if (!$this->skus->contains($sku)) {
            $this->skus->add($sku);
            $sku->setProduct($this);
        }

        return $this;
    }

    public function removeSku(Sku $sku): static
    {
        if ($this->skus->removeElement($sku)) {
            // set the owning side to null (unless already changed)
            if ($sku->getProduct() === $this) {
                $sku->setProduct(null);
            }
        }

        return $this;
    }
}
