<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn()
    : string
    {
        return Product::class;
    }

    public function configureFields(string $pageName)
    : iterable {
        yield TextField::new('sku');

        yield FormField::addFieldset('Attributes');

        yield Field::new('attributes')
            ->setFormType(ProductType::class)
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false);
    }
}
