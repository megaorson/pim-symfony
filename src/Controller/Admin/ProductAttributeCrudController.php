<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Attribute\AttributeHandlerRegistry;
use App\Attribute\AttributeTypeHandlerInterface;
use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeFactory;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductAttributeCrudController extends AbstractCrudController
{
    public function __construct(
        protected ProductAttributeFactory $attributeFactory
    ) {
    }

    public static function getEntityFqcn()
    : string
    {
        return ProductAttribute::class;
    }

    public function configureFields(string $pageName)
    : iterable {
        return [
            TextField::new('code'),
            TextField::new('name'),

            ChoiceField::new('type')
                ->setChoices($this->getAvailableTypes()),
        ];
    }

    private function getAvailableTypes()
    : array
    {
        $response = [];
        foreach ($this->attributeFactory->getAttributes() as $type => $attribute) {
            $response[ucfirst($type)] = $type;
        }

        return $response;
    }
}
