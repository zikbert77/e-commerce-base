<?php

namespace App\Form\Admin;

use App\Entity\StoreTemplateConfig;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreTemplateConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('config', TextareaType::class, [
            'required' => false,
        ]);

        $builder->get('config')->addModelTransformer(new CallbackTransformer(
            fn (?array $config) => $config === null || $config === [] ? '{}' : json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            function (?string $json) {
                $decoded = json_decode($json ?? '{}', true);
                if (!\is_array($decoded)) {
                    throw new TransformationFailedException('Must be valid JSON.');
                }

                return $decoded;
            },
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StoreTemplateConfig::class,
        ]);
    }
}
