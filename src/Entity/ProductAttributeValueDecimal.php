<?php

namespace App\Entity;

use App\Repository\ProductAttributeValueDecimalRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductAttributeValueDecimalRepository::class)]
class ProductAttributeValueDecimal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $value = null;

    #[ORM\ManyToOne(inversedBy: 'decimalValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Sku $sku = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProductAttribute $attribute = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getSku(): ?Sku
    {
        return $this->sku;
    }

    public function setSku(?Sku $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getAttribute(): ?ProductAttribute
    {
        return $this->attribute;
    }

    public function setAttribute(?ProductAttribute $attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }
}
