<?php

namespace App\Form\Admin;

use App\Entity\ProductInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductInfoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enabled', CheckboxType::class, [
                'required' => false,
            ])
            ->add('title', TextType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('slug', TextType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('shortDescription', TextareaType::class, [
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('seoTitle', TextType::class, [
                'required' => false,
                'constraints' => [new Length(max: 60)],
            ])
            ->add('seoDescription', TextareaType::class, [
                'required' => false,
                'constraints' => [new Length(max: 160)],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductInfo::class,
        ]);
    }
}
