<?php

namespace App\Form\Admin;

use App\Entity\Category;
use App\Entity\Product;
use App\Enum\BaseStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => BaseStatus::ACTIVE->value,
                    'Inactive' => BaseStatus::INACTIVE->value,
                ],
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choice_label' => fn (Category $category) => $category->getCategoryInfos()->first()?->getTitle() ?? ('#'.$category->getId()),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
