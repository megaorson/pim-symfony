<?php

namespace App\Controller\Admin;

use App\Entity\ProductAttributeValue;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class ProductAttributeValueCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductAttributeValue::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('attribute'),
            TextField::new('value'),
        ];
    }
}
