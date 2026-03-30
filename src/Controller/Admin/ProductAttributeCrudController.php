<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ProductAttribute;
use App\Service\Eav\AttributeTypeRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductAttributeCrudController extends AbstractCrudController
{
    public function __construct(
        protected readonly AttributeTypeRegistry $attributeTypeRegistry,
        private readonly TranslatorInterface $translator
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ProductAttribute::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('code', $this->translator->trans('admin.product_attribute.field.code')),
            TextField::new('name', $this->translator->trans('admin.product_attribute.field.name')),
            ChoiceField::new('type', $this->translator->trans('admin.product_attribute.field.type'))->setChoices($this->getAvailableTypes()),
        ];
    }

    private function getAvailableTypes(): array
    {
        $response = [];
        foreach ($this->attributeTypeRegistry->all() as $type => $attribute) {
            $response[ucfirst($type)] = $type;
        }
        return $response;
    }
}
