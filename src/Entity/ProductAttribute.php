<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductAttributeRepository;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Metadata\ApiResource;

#[ORM\Entity(repositoryClass: ProductAttributeRepository::class)]
#[ApiResource(
    description: 'List of available product attributes',
)]
class ProductAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $code = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function getId()
    : ?int
    {
        return $this->id;
    }

    public function getCode()
    : ?string
    {
        return $this->code;
    }

    public function setCode(string $code)
    : static {
        $this->code = $code;

        return $this;
    }

    public function getType()
    : ?string
    {
        return $this->type;
    }

    public function setType(string $type)
    : static {
        $this->type = $type;

        return $this;
    }

    public function getName()
    : ?string
    {
        return $this->name;
    }

    public function setName(string $name)
    : static {
        $this->name = $name;

        return $this;
    }
}
