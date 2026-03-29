<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $sku = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductAttributeValueDecimal::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $decimalValues;

    /**
     * @var Collection<int, ProductAttributeValueText>
     */
    #[ORM\OneToMany(targetEntity: ProductAttributeValueText::class, mappedBy: 'product', cascade: ['persist'], orphanRemoval: true)]
    private Collection $textValues;

    /**
     * @var Collection<int, ProductAttributeValueInt>
     */
    #[ORM\OneToMany(targetEntity: ProductAttributeValueInt::class, mappedBy: 'product', cascade: ['persist'], orphanRemoval: true)]
    private Collection $intValues;

    /**
     * @var Collection<int, ProductAttributeValueImage>
     */
    #[ORM\OneToMany(targetEntity: ProductAttributeValueImage::class, mappedBy: 'product', cascade: ['persist'], orphanRemoval: true)]
    private Collection $imageValues;

    public function __construct()
    {
        $this->decimalValues = new ArrayCollection();
        $this->textValues = new ArrayCollection();
        $this->intValues = new ArrayCollection();
        $this->imageValues = new ArrayCollection();
    }

    public function getAttributes()
    : array
    {
        return [];
    }

    public function getAllAttributeValues(): array
    {
        return array_merge(
            $this->textValues?->toArray() ?? [],
            $this->intValues?->toArray() ?? [],
            $this->decimalValues?->toArray() ?? [],
            $this->imageValues?->toArray() ?? []
        );
    }

    /**
     * Retrieves the ID.
     * @return int|null The ID value or null if not set.
     */
    public function getId()
    : ?int
    {
        return $this->id;
    }

    public function getSku()
    : ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku)
    : static {
        $this->sku = $sku;

        return $this;
    }

    /**
     * @return Collection<int, ProductAttributeValueDecimal>
     */
    public function getDecimalValues()
    : Collection
    {
        return $this->decimalValues;
    }

    public function addDecimalValue(ProductAttributeValueDecimal $productAttributeValueDecimal)
    : static {
        if (!$this->decimalValues->contains($productAttributeValueDecimal)) {
            $this->decimalValues->add($productAttributeValueDecimal);
            $productAttributeValueDecimal->setProduct($this);
        }

        return $this;
    }

    public function removeDecimalValue(ProductAttributeValueDecimal $productAttributeValueDecimal)
    : static {
        if ($this->decimalValues->removeElement($productAttributeValueDecimal)) {
            // set the owning side to null (unless already changed)
            if ($productAttributeValueDecimal->getProduct() === $this) {
                $productAttributeValueDecimal->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductAttributeValueText>
     */
    public function getTextValues()
    : Collection
    {
        return $this->textValues;
    }

    public function addTextValue(ProductAttributeValueText $textValue)
    : static {
        if (!$this->textValues->contains($textValue)) {
            $this->textValues->add($textValue);
            $textValue->setProduct($this);
        }

        return $this;
    }

    public function removeTextValue(ProductAttributeValueText $textValue)
    : static {
        if ($this->textValues->removeElement($textValue)) {
            // set the owning side to null (unless already changed)
            if ($textValue->getProduct() === $this) {
                $textValue->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductAttributeValueInt>
     */
    public function getIntValues()
    : Collection
    {
        return $this->intValues;
    }

    public function addIntValue(ProductAttributeValueInt $intValue)
    : static {
        if (!$this->intValues->contains($intValue)) {
            $this->intValues->add($intValue);
            $intValue->setProduct($this);
        }

        return $this;
    }

    public function removeIntValue(ProductAttributeValueInt $intValue)
    : static {
        if ($this->intValues->removeElement($intValue)) {
            // set the owning side to null (unless already changed)
            if ($intValue->getProduct() === $this) {
                $intValue->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductAttributeValueImage>
     */
    public function getImageValues()
    : Collection
    {
        return $this->imageValues;
    }

    public function addImageValue(ProductAttributeValueImage $imageValue)
    : static {
        if (!$this->imageValues->contains($imageValue)) {
            $this->imageValues->add($imageValue);
            $imageValue->setProduct($this);
        }

        return $this;
    }

    public function removeImageValue(ProductAttributeValueImage $imageValue)
    : static {
        if ($this->imageValues->removeElement($imageValue)) {
            // set the owning side to null (unless already changed)
            if ($imageValue->getProduct() === $this) {
                $imageValue->setProduct(null);
            }
        }

        return $this;
    }
}
