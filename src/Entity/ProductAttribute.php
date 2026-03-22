<?php

namespace App\Entity;

use App\Repository\ProductAttributeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductAttributeRepository::class)]
class ProductAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $ccode = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, ProductAttributeValue>
     */
    #[ORM\OneToMany(targetEntity: ProductAttributeValue::class, mappedBy: 'attribute', orphanRemoval: true)]
    private Collection $productAttributeValues;

    public function __construct()
    {
        $this->productAttributeValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->ccode;
    }

    public function setCode(string $ccode): static
    {
        $this->ccode = $ccode;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, ProductAttributeValue>
     */
    public function getProductAttributeValues(): Collection
    {
        return $this->productAttributeValues;
    }

    public function addProductAttributeValue(ProductAttributeValue $productAttributeValue): static
    {
        if (!$this->productAttributeValues->contains($productAttributeValue)) {
            $this->productAttributeValues->add($productAttributeValue);
            $productAttributeValue->setAttribute($this);
        }

        return $this;
    }

    public function removeProductAttributeValue(ProductAttributeValue $productAttributeValue): static
    {
        if ($this->productAttributeValues->removeElement($productAttributeValue)) {
            // set the owning side to null (unless already changed)
            if ($productAttributeValue->getAttribute() === $this) {
                $productAttributeValue->setAttribute(null);
            }
        }

        return $this;
    }
}
