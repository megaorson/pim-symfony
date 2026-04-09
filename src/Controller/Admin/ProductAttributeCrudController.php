<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ProductAttribute;
use App\Service\Eav\AttributeTypeRegistry;
use App\Service\Eav\ProductAttributeUsageChecker;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductAttributeCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AttributeTypeRegistry $attributeTypeRegistry,
        private readonly TranslatorInterface $translator,
        private readonly ProductAttributeUsageChecker $usageChecker,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ProductAttribute::class;
    }

    public function deleteEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof ProductAttribute) {
            return;
        }

        if ($this->usageChecker->isUsed($entityInstance)) {
            $this->addFlash(
                'danger',
                $this->translator->trans('product_attribute.delete_forbidden_in_use', [
                    '%code%' => $entityInstance->getCode(),
                ])
            );

            return;
        }

        parent::deleteEntity($em, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', $this->translator->trans('admin.product_attribute.field.name'));

        if (Crud::PAGE_EDIT === $pageName) {
            yield TextField::new('code', $this->translator->trans('admin.product_attribute.field.code'))
                ->setDisabled();

            yield ChoiceField::new('type', $this->translator->trans('admin.product_attribute.field.type'))
                ->setChoices($this->getAvailableTypes())
                ->setDisabled();
        } else {
            yield TextField::new('code', $this->translator->trans('admin.product_attribute.field.code'));

            yield ChoiceField::new('type', $this->translator->trans('admin.product_attribute.field.type'))
                ->setChoices($this->getAvailableTypes());
        }

        yield BooleanField::new('isRequired', $this->translator->trans('admin.product_attribute.field.isRequired'));
        yield BooleanField::new('isSelectable', $this->translator->trans('admin.product_attribute.field.isSelectable'));
        yield BooleanField::new('isFilterable', $this->translator->trans('admin.product_attribute.field.isFilterable'));
        yield BooleanField::new('isSortable', $this->translator->trans('admin.product_attribute.field.isSortable'));
    }

    private function getAvailableTypes(): array
    {
        $response = [];

        foreach ($this->attributeTypeRegistry->all() as $type => $_) {
            $response[ucfirst($type)] = $type;
        }

        return $response;
    }
}
