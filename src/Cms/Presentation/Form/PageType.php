<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Form;

use App\Cms\Domain\Entity\Page;
use App\Language\Application\SystemTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;

final class PageType extends AbstractType
{
    public function __construct(private readonly SluggerInterface $slugger, private readonly SystemTranslator $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $checkbox = ['required' => false];
        $t = $this->translator->translate(...);
        $builder
            ->add('title', TextType::class, ['label' => $t('page.title')])
            ->add('slug', TextType::class, ['label' => 'Slug', 'required' => false, 'help' => $t('page.slug_help')])
            ->add('caption', TextareaType::class, ['label' => $t('page.caption'), 'required' => false, 'attr' => ['rows' => 3, 'maxlength' => 600]])
            ->add('seoTitle', TextType::class, ['label' => $t('page.seo_title'), 'required' => false, 'attr' => ['maxlength' => 200]])
            ->add('access', ChoiceType::class, ['label' => $t('page.access'), 'choices' => [$t('page.access_public') => 'Public', $t('page.access_registered') => 'Registered', $t('page.access_membership') => 'Membership']])
            ->add('canonical', TextType::class, ['label' => $t('page.canonical'), 'required' => false])
            ->add('published', CheckboxType::class, $checkbox + ['label' => $t('page.active_published')])
            ->add('follow', CheckboxType::class, $checkbox + ['label' => $t('page.follow')])
            ->add('editorMode', HiddenType::class)
            ->add('content', HiddenType::class, ['required' => false, 'empty_data' => ''])
            ->add('builderData', HiddenType::class, ['required' => false, 'empty_data' => ''])
            ->add('builderCss', HiddenType::class, ['required' => false, 'empty_data' => ''])
            ->add('description', TextareaType::class, ['label' => $t('page.meta_description'), 'required' => false, 'attr' => ['rows' => 3, 'maxlength' => 160]])
            ->add('keywords', TextareaType::class, ['label' => $t('page.keywords'), 'required' => false, 'attr' => ['rows' => 3]])
            ->add('meta', TextareaType::class, ['label' => $t('page.extra_meta'), 'required' => false, 'attr' => ['rows' => 3]])
            ->add('javascript', TextareaType::class, ['label' => $t('page.javascript'), 'required' => false, 'attr' => ['rows' => 5]])
            ->add('homePage', CheckboxType::class, $checkbox + ['label' => $t('page.role_home')])
            ->add('errorPage', CheckboxType::class, $checkbox + ['label' => $t('page.role_error')])
            ->add('adminOnly', CheckboxType::class, $checkbox + ['label' => $t('page.role_admin')])
            ->add('loginPage', CheckboxType::class, $checkbox + ['label' => $t('page.role_login')])
            ->add('activationPage', CheckboxType::class, $checkbox + ['label' => $t('page.role_activation')])
            ->add('accountPage', CheckboxType::class, $checkbox + ['label' => $t('page.role_account')])
            ->add('registrationPage', CheckboxType::class, $checkbox + ['label' => $t('page.role_registration')])
            ->add('searchPage', CheckboxType::class, $checkbox + ['label' => $t('page.role_search')])
            ->add('sitemapPage', CheckboxType::class, $checkbox + ['label' => $t('page.role_sitemap')])
            ->add('profilePage', CheckboxType::class, $checkbox + ['label' => $t('page.role_profile')])
            ->add('termsPage', CheckboxType::class, $checkbox + ['label' => $t('page.role_terms')]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!is_array($data) || '' !== trim((string) ($data['slug'] ?? ''))) {
                return;
            }

            $data['slug'] = $this->slugger->slug((string) ($data['title'] ?? ''))->lower()->toString();
            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Page::class]);
    }
}
