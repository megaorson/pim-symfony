<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Attribute\AttributeHandlerRegistry;
use App\Attribute\AttributeTypeHandlerInterface;
use App\Entity\ProductAttribute;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductAttributeCrudController extends AbstractCrudController
{
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
                ->setChoices([
                    'Text'    => 'text',
                    'Decimal' => 'decimal',
                    'Image'   => 'image',
                    'Integer' => 'int',
                ]),
        ];
    }

    private function getAvailableTypes()
    : array
    {
        $response = [];

        return $response;
    }
}
