<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Form;

use App\Cms\Domain\Entity\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $checkbox = ['required' => false];
        $builder
            ->add('title', TextType::class, ['label' => 'Tytuł'])
            ->add('slug', TextType::class, ['label' => 'Slug', 'help' => 'Np. o-nas; bez ukośników i polskich znaków.'])
            ->add('caption', TextareaType::class, ['label' => 'Podpis / zajawka', 'required' => false, 'attr' => ['rows' => 3, 'maxlength' => 600]])
            ->add('seoTitle', TextType::class, ['label' => 'Tytuł SEO', 'required' => false, 'attr' => ['maxlength' => 200]])
            ->add('access', ChoiceType::class, ['label' => 'Dostęp', 'choices' => ['Publiczny' => 'Public', 'Zalogowani' => 'Registered', 'Członkostwo' => 'Membership']])
            ->add('canonical', TextType::class, ['label' => 'Adres canonical', 'required' => false])
            ->add('published', CheckboxType::class, $checkbox + ['label' => 'Aktywna / opublikowana'])
            ->add('follow', CheckboxType::class, $checkbox + ['label' => 'Śledzenie linków przez roboty'])
            ->add('editorMode', HiddenType::class)
            ->add('content', HiddenType::class, ['required' => false])
            ->add('builderData', HiddenType::class, ['required' => false])
            ->add('builderCss', HiddenType::class, ['required' => false])
            ->add('description', TextareaType::class, ['label' => 'Opis meta', 'required' => false, 'attr' => ['rows' => 3, 'maxlength' => 160]])
            ->add('keywords', TextareaType::class, ['label' => 'Słowa kluczowe', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('meta', TextareaType::class, ['label' => 'Dodatkowe meta', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('javascript', TextareaType::class, ['label' => 'Kod JavaScript strony', 'required' => false, 'attr' => ['rows' => 5]])
            ->add('homePage', CheckboxType::class, $checkbox + ['label' => 'Strona główna'])
            ->add('errorPage', CheckboxType::class, $checkbox + ['label' => 'Strona błędu 404'])
            ->add('adminOnly', CheckboxType::class, $checkbox + ['label' => 'Tylko administrator'])
            ->add('loginPage', CheckboxType::class, $checkbox + ['label' => 'Strona logowania'])
            ->add('activationPage', CheckboxType::class, $checkbox + ['label' => 'Strona aktywacji'])
            ->add('accountPage', CheckboxType::class, $checkbox + ['label' => 'Strona konta'])
            ->add('registrationPage', CheckboxType::class, $checkbox + ['label' => 'Strona rejestracji'])
            ->add('searchPage', CheckboxType::class, $checkbox + ['label' => 'Strona wyszukiwania'])
            ->add('sitemapPage', CheckboxType::class, $checkbox + ['label' => 'Mapa witryny'])
            ->add('profilePage', CheckboxType::class, $checkbox + ['label' => 'Strona profilu'])
            ->add('termsPage', CheckboxType::class, $checkbox + ['label' => 'Regulamin']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Page::class]);
    }
}
