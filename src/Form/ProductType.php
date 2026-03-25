<?php
declare(strict_types=1);

namespace App\Form;

use App\Form\Attribute\AttributeFormRegistry;
use App\Repository\ProductAttributeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function __construct(
        private ProductAttributeRepository $attributeRepository,
        private AttributeFormRegistry      $attributeFormRegistry
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $attributeFormRegistry = $this->attributeFormRegistry;
        $attributeRepository = $this->attributeRepository;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($attributeFormRegistry, $attributeRepository)
        {
            $attributeFormRegistry->build(
                $event->getForm(),
                $attributeRepository->findAll(),
                $event->getForm()->getParent()?->getData()
            );
        }
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT, function (FormEvent $event) use ($attributeFormRegistry, $attributeRepository)
        {
            $attributeFormRegistry->handleSubmit(
                $event->getForm(),
                $attributeRepository->findAll(),
                $event->getForm()->getParent()->getData()
            );
        }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    : void {
        $resolver->setDefaults([
            'product' => null,
        ]);
    }
}
