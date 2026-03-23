<?php

namespace App\Controller\Admin;

use App\Entity\Sku;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;

class SkuCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Sku::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('sku'),
            MoneyField::new('price')->setCurrency('USD'),

        ];
    }
}
