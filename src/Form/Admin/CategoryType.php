<?php

namespace App\Form\Admin;

use App\Entity\Category;
use App\Enum\BaseStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
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
            ->add('parent', EntityType::class, [
                'class' => Category::class,
                'required' => false,
                'placeholder' => '— none (top level) —',
                'choice_label' => fn (Category $category) => $category->getCategoryInfos()->first()?->getTitle() ?? ('#'.$category->getId()),
                'query_builder' => function (\App\Repository\CategoryRepository $repository) use ($options) {
                    $qb = $repository->createQueryBuilder('c')->orderBy('c.id', 'ASC');
                    if ($options['editing_category'] !== null) {
                        $qb->andWhere('c != :self')->setParameter('self', $options['editing_category']);
                    }

                    return $qb;
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'editing_category' => null,
        ]);
    }
}
