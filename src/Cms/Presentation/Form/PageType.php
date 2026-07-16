<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Form;

use App\Cms\Domain\Entity\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Tytuł'])
            ->add('slug', TextType::class, ['label' => 'Slug', 'help' => 'Np. o-nas; bez ukośników i polskich znaków.'])
            ->add('content', TextareaType::class, ['label' => 'Treść', 'attr' => ['rows' => 18]])
            ->add('published', CheckboxType::class, ['label' => 'Opublikowana', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Page::class]);
    }
}
