<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('sku', $this->translator->trans('admin.product.field.sku'));
        yield FormField::addFieldset($this->translator->trans('admin.product.fieldset.attributes'));
        yield Field::new('attributes', $this->translator->trans('admin.product.field.attributes'))
            ->setFormType(ProductType::class)
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false);
    }
}
