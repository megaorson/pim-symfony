<?php
declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use App\Repository\ProductAttributeValueTextRepository;
use App\Subscriber\TimestampSubscriber;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductAttributeValueTextRepository::class)]
#[ORM\EntityListeners([TimestampSubscriber::class])]
class ProductAttributeValueText extends ProductAttributeValueAbstract implements TimestampableInterface
{
    use TimestampableTrait;

    public const TYPE = 'text';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProductAttribute $attribute = null;

    #[ORM\ManyToOne(inversedBy: 'textValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue($value): static
    {
        $this->value = (string)$value;

        return $this;
    }

    public function getAttribute(): ?ProductAttribute
    {
        return $this->attribute;
    }

    public function setAttribute(?ProductAttribute $productAttribute): static
    {
        $this->attribute = $productAttribute;

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
}
