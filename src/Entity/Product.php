<?php
declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use App\Repository\ProductRepository;
use App\Subscriber\TimestampSubscriber;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\EntityListeners([TimestampSubscriber::class])]
class Product implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $sku = null;

    /**
     * @var Collection<int, ProductAttributeValueDecimal>
     */
    #[ORM\OneToMany(
        mappedBy: 'product',
        targetEntity: ProductAttributeValueDecimal::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $decimalValues;

    /**
     * @var Collection<int, ProductAttributeValueText>
     */
    #[ORM\OneToMany(
        targetEntity: ProductAttributeValueText::class,
        mappedBy: 'product',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $textValues;

    /**
     * @var Collection<int, ProductAttributeValueInt>
     */
    #[ORM\OneToMany(
        targetEntity: ProductAttributeValueInt::class,
        mappedBy: 'product',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $intValues;

    /**
     * @var Collection<int, ProductAttributeValueImage>
     */
    #[ORM\OneToMany(
        targetEntity: ProductAttributeValueImage::class,
        mappedBy: 'product',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $imageValues;

    public function __construct()
    {
        $this->decimalValues = new ArrayCollection();
        $this->textValues = new ArrayCollection();
        $this->intValues = new ArrayCollection();
        $this->imageValues = new ArrayCollection();
    }

    /**
     * @return list<ProductAttributeTypeInterface>
     */
    public function getAttributes(): array
    {
        return $this->getAllAttributeValues();
    }

    public function getAttributeValue(string $code): mixed
    {
        return $this->getAttributeValueObject($code)?->getValue();
    }

    public function hasAttributeValue(string $code): bool
    {
        return $this->getAttributeValueObject($code) !== null;
    }

    public function getAttributeValueObject(string $code): ?ProductAttributeTypeInterface
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        foreach ($this->getAllAttributeValues() as $attributeValue) {
            $attribute = $attributeValue->getAttribute();

            if ($attribute === null) {
                continue;
            }

            if ($attribute->getCode() !== $code) {
                continue;
            }

            return $attributeValue;
        }

        return null;
    }

    public function removeAttributeValueObject(ProductAttributeTypeInterface $attributeValue): void
    {
        $method = $this->resolveRemoveMethodName($attributeValue);

        if (!method_exists($this, $method)) {
            throw new \LogicException(sprintf(
                'Remove method "%s" does not exist for attribute value class "%s".',
                $method,
                $attributeValue::class
            ));
        }

        $this->$method($attributeValue);
    }

    public function addAttributeValueObject(ProductAttributeTypeInterface $attributeValue): void
    {
        $method = $this->resolveAddMethodName($attributeValue);

        if (!method_exists($this, $method)) {
            throw new \LogicException(sprintf(
                'Add method "%s" does not exist for attribute value class "%s".',
                $method,
                $attributeValue::class
            ));
        }

        $this->$method($attributeValue);
    }

    private function resolveAddMethodName(ProductAttributeTypeInterface $attributeValue): string
    {
        $shortName = (new \ReflectionClass($attributeValue))->getShortName();

        if (!str_starts_with($shortName, 'ProductAttributeValue')) {
            throw new \LogicException(sprintf(
                'Unsupported attribute value class "%s".',
                $attributeValue::class
            ));
        }

        $suffix = substr($shortName, strlen('ProductAttributeValue'));

        if ($suffix === '' || $suffix === false) {
            throw new \LogicException(sprintf(
                'Cannot resolve add method for attribute value class "%s".',
                $attributeValue::class
            ));
        }

        return sprintf('add%sValue', $suffix);
    }

    private function resolveRemoveMethodName(ProductAttributeTypeInterface $attributeValue): string
    {
        $shortName = (new \ReflectionClass($attributeValue))->getShortName();

        if (!str_starts_with($shortName, 'ProductAttributeValue')) {
            throw new \LogicException(sprintf(
                'Unsupported attribute value class "%s".',
                $attributeValue::class
            ));
        }

        $suffix = substr($shortName, strlen('ProductAttributeValue'));

        if ($suffix === '' || $suffix === false) {
            throw new \LogicException(sprintf(
                'Cannot resolve remove method for attribute value class "%s".',
                $attributeValue::class
            ));
        }

        return sprintf('remove%sValue', $suffix);
    }

    /**
     * @return list<ProductAttributeTypeInterface>
     */
    public function getAllAttributeValues(): array
    {
        return array_merge(
            $this->textValues->toArray(),
            $this->intValues->toArray(),
            $this->decimalValues->toArray(),
            $this->imageValues->toArray()
        );
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

    /**
     * @return Collection<int, ProductAttributeValueDecimal>
     */
    public function getDecimalValues(): Collection
    {
        return $this->decimalValues;
    }

    public function addDecimalValue(ProductAttributeValueDecimal $productAttributeValueDecimal): static
    {
        if (!$this->decimalValues->contains($productAttributeValueDecimal)) {
            $this->decimalValues->add($productAttributeValueDecimal);
            $productAttributeValueDecimal->setProduct($this);
        }

        return $this;
    }

    public function removeDecimalValue(ProductAttributeValueDecimal $productAttributeValueDecimal): static
    {
        if ($this->decimalValues->removeElement($productAttributeValueDecimal)) {
            if ($productAttributeValueDecimal->getProduct() === $this) {
                $productAttributeValueDecimal->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductAttributeValueText>
     */
    public function getTextValues(): Collection
    {
        return $this->textValues;
    }

    public function addTextValue(ProductAttributeValueText $textValue): static
    {
        if (!$this->textValues->contains($textValue)) {
            $this->textValues->add($textValue);
            $textValue->setProduct($this);
        }

        return $this;
    }

    public function removeTextValue(ProductAttributeValueText $textValue): static
    {
        if ($this->textValues->removeElement($textValue)) {
            if ($textValue->getProduct() === $this) {
                $textValue->setProduct(null);
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
            $intValue->setProduct($this);
        }

        return $this;
    }

    public function removeIntValue(ProductAttributeValueInt $intValue): static
    {
        if ($this->intValues->removeElement($intValue)) {
            if ($intValue->getProduct() === $this) {
                $intValue->setProduct(null);
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
            $imageValue->setProduct($this);
        }

        return $this;
    }

    public function removeImageValue(ProductAttributeValueImage $imageValue): static
    {
        if ($this->imageValues->removeElement($imageValue)) {
            if ($imageValue->getProduct() === $this) {
                $imageValue->setProduct(null);
            }
        }

        return $this;
    }
}
