<?php

namespace App\Entity;

use App\Repository\SkuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkuRepository::class)]
class Sku
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $sku = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\ManyToOne(inversedBy: 'skus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\OneToMany(mappedBy: 'sku', targetEntity: ProductAttributeValue::class, orphanRemoval: true)]
    private ?Collection $attributeValues = null;

    public function __construct()
    {
        $this->attributeValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getAttributeValues(): Collection
    {
        return $this->attributeValues;
    }

    public function addAttributeValue(ProductAttributeValue $value): self
    {
        if (!$this->attributeValues->contains($value)) {
            $this->attributeValues->add($value);
            $value->setSku($this);
        }

        return $this;
    }

    public function removeAttributeValue(ProductAttributeValue $value): self
    {
        if ($this->attributeValues->removeElement($value)) {
            if ($value->getSku() === $this) {
                $value->setSku(null);
            }
        }

        return $this;
    }
}
