<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductAttributeValueIntRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductAttributeValueIntRepository::class)]
class ProductAttributeValueInt extends ProductAttributeValueAbstract
{
    public const TYPE = 'int';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $value = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProductAttribute $attribute = null;

    #[ORM\ManyToOne(inversedBy: 'intValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    public function getId()
    : ?int
    {
        return $this->id;
    }

    public function getValue()
    : ?int
    {
        return $this->value;
    }

    public function setValue($value)
    : static {
        $this->value = (int)$value;

        return $this;
    }

    public function getAttribute()
    : ?ProductAttribute
    {
        return $this->attribute;
    }

    public function setAttribute(?ProductAttribute $attribute)
    : static {
        $this->attribute = $attribute;

        return $this;
    }

    public function getProduct()
    : ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product)
    : static {
        $this->product = $product;

        return $this;
    }
}
