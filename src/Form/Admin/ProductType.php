<?php

namespace App\Form\Admin;

use App\Entity\Category;
use App\Entity\Product;
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
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => function (Category $category): string {
                    $info = $category->getCategoryInfos()->first();
                    return $info ? $info->getTitle() : 'Category #' . $category->getId();
                },
                'placeholder' => 'Select a category',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => 1,
                    'Inactive' => 0,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('productInfo', ProductInfoType::class, [
                'mapped' => false,
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
