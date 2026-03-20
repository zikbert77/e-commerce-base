<?php

namespace App\Form\Admin;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['class' => 'form-control'],
            ])
            ->add('first_name', TextType::class, [
                'attr' => ['class' => 'form-control'],
            ])
            ->add('last_name', TextType::class, [
                'attr' => ['class' => 'form-control'],
            ])
            ->add('middle_name', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => 1,
                    'Inactive' => 0,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('is_verified', CheckboxType::class, [
                'required' => false,
                'label' => 'Email verified',
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Administrator' => 'ROLE_ADMIN',
                    'User' => 'ROLE_USER',
                ],
                'expanded' => true,
                'multiple' => true,
                'label' => 'Roles',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
