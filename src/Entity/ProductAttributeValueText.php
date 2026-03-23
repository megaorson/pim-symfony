<?php

namespace App\Entity;

use App\Repository\ProductAttributeValueTextRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductAttributeValueTextRepository::class)]
class ProductAttributeValueText
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    #[ORM\ManyToOne(inversedBy: 'textValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Sku $sku = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProductAttribute $attribute = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
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

    public function getProductAttribute(): ?ProductAttribute
    {
        return $this->attribute;
    }

    public function setProductAttribute(?ProductAttribute $productAttribute): static
    {
        $this->attribute = $productAttribute;

        return $this;
    }
}
