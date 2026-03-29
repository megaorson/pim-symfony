<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ProductAttribute;
use App\Service\Eav\AttributeTypeRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductAttributeCrudController extends AbstractCrudController
{
    public function __construct(
        protected AttributeTypeRegistry $attributeTypeRegistry
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
        foreach ($this->attributeTypeRegistry->all() as $type => $attribute) {
            $response[ucfirst($type)] = $type;
        }

        return $response;
    }
}
