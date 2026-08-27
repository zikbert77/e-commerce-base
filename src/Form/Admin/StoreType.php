<?php

namespace App\Form\Admin;

use App\Entity\Store;
use App\Entity\Template;
use App\Enum\BaseStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class StoreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('status', EnumType::class, [
                'class' => BaseStatus::class,
                'choice_label' => fn (BaseStatus $status) => ucfirst(strtolower($status->name)),
            ])
            ->add('template', EntityType::class, [
                'class' => Template::class,
                'required' => false,
                'placeholder' => '— none —',
                'choice_label' => fn (Template $template) => $template->getTitle().' ('.$template->getCode().')',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Store::class,
        ]);
    }
}
