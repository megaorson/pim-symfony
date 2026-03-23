<?php
declare(strict_types=1);

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

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\ManyToOne(inversedBy: 'skus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    /**
     * @var Collection<int, ProductAttributeValueText>
     */
    #[ORM\OneToMany(targetEntity: ProductAttributeValueText::class, cascade: ['persist'], mappedBy: 'sku', orphanRemoval: true)]
    private Collection $textValues;

    /**
     * @var Collection<int, ProductAttributeValueInt>
     */
    #[ORM\OneToMany(targetEntity: ProductAttributeValueInt::class, cascade: ['persist'], mappedBy: 'sku', orphanRemoval: true)]
    private Collection $intValues;

    /**
     * @var Collection<int, ProductAttributeValueImage>
     */
    #[ORM\OneToMany(targetEntity: ProductAttributeValueImage::class, cascade: ['persist'], mappedBy: 'sku', orphanRemoval: true)]
    private Collection $imageValues;

    /**
     * @var Collection<int, ProductAttributeValueDecimal>
     */
    #[ORM\OneToMany(targetEntity: ProductAttributeValueDecimal::class, cascade: ['persist'], mappedBy: 'sku', orphanRemoval: true)]
    private Collection $decimalValues;

    public function __construct()
    {
        $this->textValues = new ArrayCollection();
        $this->intValues = new ArrayCollection();
        $this->imageValues = new ArrayCollection();
        $this->decimalValues = new ArrayCollection();
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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
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

    /**
     * @return Collection<int, ProductAttributeValueText>
     */
    public function getTextValues(): Collection
    {
        return $this->textValues;
    }

    public function addTextValue(ProductAttributeValueText $productAttributeValueText): static
    {
        if (!$this->textValues->contains($productAttributeValueText)) {
            $this->textValues->add($productAttributeValueText);
            $productAttributeValueText->setSku($this);
        }

        return $this;
    }

    public function removeTextValue(ProductAttributeValueText $productAttributeValueText): static
    {
        if ($this->textValues->removeElement($productAttributeValueText)) {
            // set the owning side to null (unless already changed)
            if ($productAttributeValueText->getSku() === $this) {
                $productAttributeValueText->setSku(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductAttributeValueInt>
     */
    public function getIntValues(): Collection
    {
        return $this->intValues;
    }

    public function addIntValue(ProductAttributeValueInt $intValue): static
    {
        if (!$this->intValues->contains($intValue)) {
            $this->intValues->add($intValue);
            $intValue->setSku($this);
        }

        return $this;
    }

    public function removeIntValue(ProductAttributeValueInt $intValue): static
    {
        if ($this->intValues->removeElement($intValue)) {
            // set the owning side to null (unless already changed)
            if ($intValue->getSku() === $this) {
                $intValue->setSku(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductAttributeValueImage>
     */
    public function getImageValues(): Collection
    {
        return $this->imageValues;
    }

    public function addImageValue(ProductAttributeValueImage $imageValue): static
    {
        if (!$this->imageValues->contains($imageValue)) {
            $this->imageValues->add($imageValue);
            $imageValue->setSku($this);
        }

        return $this;
    }

    public function removeImageValue(ProductAttributeValueImage $imageValue): static
    {
        if ($this->imageValues->removeElement($imageValue)) {
            // set the owning side to null (unless already changed)
            if ($imageValue->getSku() === $this) {
                $imageValue->setSku(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductAttributeValueDecimal>
     */
    public function getDecimalValues(): Collection
    {
        return $this->decimalValues;
    }

    public function addDecimalValue(ProductAttributeValueDecimal $decimalValue): static
    {
        if (!$this->decimalValues->contains($decimalValue)) {
            $this->decimalValues->add($decimalValue);
            $decimalValue->setSku($this);
        }

        return $this;
    }

    public function removeDecimalValue(ProductAttributeValueDecimal $decimalValue): static
    {
        if ($this->decimalValues->removeElement($decimalValue)) {
            // set the owning side to null (unless already changed)
            if ($decimalValue->getSku() === $this) {
                $decimalValue->setSku(null);
            }
        }

        return $this;
    }
}
