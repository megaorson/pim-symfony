<?php

namespace App\Entity;

use App\Repository\ProductAttributeValueIntRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductAttributeValueIntRepository::class)]
class ProductAttributeValueInt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $value = null;

    #[ORM\ManyToOne(inversedBy: 'intValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Sku $sku = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProductAttribute $attribute = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): static
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
