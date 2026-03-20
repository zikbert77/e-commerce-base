<?php

namespace App\Form\Admin;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $excludeId = $options['exclude_category'];

        $builder
            ->add('parent', EntityType::class, [
                'class' => Category::class,
                'choice_label' => function (Category $category): string {
                    $info = $category->getCategoryInfos()->first();
                    return $info ? $info->getTitle() : 'Category #' . $category->getId();
                },
                'query_builder' => function (CategoryRepository $repo) use ($excludeId) {
                    $qb = $repo->createQueryBuilder('c');
                    if ($excludeId !== null) {
                        $qb->where('c.id != :excluded')->setParameter('excluded', $excludeId);
                    }
                    return $qb;
                },
                'required' => false,
                'placeholder' => 'None (top-level category)',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => 1,
                    'Inactive' => 0,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('categoryInfo', CategoryInfoType::class, [
                'mapped' => false,
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'exclude_category' => null,
        ]);
        $resolver->setAllowedTypes('exclude_category', ['null', 'int']);
    }
}
