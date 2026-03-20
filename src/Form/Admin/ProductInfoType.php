<?php

namespace App\Form\Admin;

use App\Entity\ProductInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductInfoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => ['class' => 'form-control'],
            ])
            ->add('slug', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'help' => 'URL-friendly identifier (e.g. my-product-name)',
            ])
            ->add('short_description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 8],
            ])
            ->add('seoTitle', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'maxlength' => 60],
                'help' => 'Max 60 characters',
            ])
            ->add('seoDescription', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 2, 'maxlength' => 160],
                'help' => 'Max 160 characters',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductInfo::class,
        ]);
    }
}
