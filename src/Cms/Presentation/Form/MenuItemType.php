<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Form;

use App\Cms\Application\MenuContentRegistry;
use App\Cms\Domain\Entity\MenuItem;
use App\Cms\Domain\Entity\Page;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Language\Application\SystemTranslator;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MenuItemType extends AbstractType
{
    public function __construct(private readonly SystemTranslator $translator, private readonly MenuContentRegistry $moduleContent) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $t = $this->translator->translate(...);
        $builder
            ->add('name', TextType::class, ['label' => $t('menu.name')])
            ->add('caption', TextType::class, ['label' => $t('menu.caption'), 'required' => false])
            ->add('parent', EntityType::class, ['class' => MenuItem::class, 'choice_label' => 'name', 'label' => $t('menu.parent'), 'placeholder' => $t('menu.root_level'), 'required' => false])
            ->add('contentType', ChoiceType::class, ['label' => $t('menu.type'), 'choices' => [$t('menu.type_page') => MenuItem::TYPE_PAGE, $t('menu.type_module') => MenuItem::TYPE_MODULE, $t('menu.type_web') => MenuItem::TYPE_WEB, $t('menu.type_placeholder') => MenuItem::TYPE_PLACEHOLDER], 'attr' => ['data-menu-content-type' => '']])
            ->add('page', EntityType::class, ['class' => Page::class, 'query_builder' => static fn (PageRepository $pages) => $pages->createQueryBuilder('page')->andWhere('page.deletedAt IS NULL')->orderBy('page.title', 'ASC'), 'choice_label' => 'title', 'label' => $t('pages.page'), 'placeholder' => $t('menu.select_page'), 'required' => false, 'row_attr' => ['data-menu-field' => 'page']])
            ->add('moduleReference', ChoiceType::class, ['label' => $t('menu.module_content'), 'placeholder' => $t('menu.select_module_content'), 'choices' => $this->moduleContent->formChoices(), 'required' => false, 'row_attr' => ['data-menu-field' => 'module']])
            ->add('link', TextType::class, ['label' => $t('menu.external_link'), 'help' => $t('menu.link_help'), 'required' => false, 'row_attr' => ['data-menu-field' => 'web']])
            ->add('target', ChoiceType::class, ['label' => $t('menu.link_target'), 'choices' => [$t('menu.same_tab') => '_self', $t('menu.new_tab') => '_blank']])
            ->add('place', ChoiceType::class, ['label' => $t('menu.place'), 'choices' => [$t('menu.header') => MenuItem::PLACE_HEADER, $t('menu.footer') => MenuItem::PLACE_FOOTER]])
            ->add('homePage', CheckboxType::class, ['label' => $t('menu.home_link'), 'required' => false])
            ->add('active', CheckboxType::class, ['label' => $t('common.active'), 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MenuItem::class]);
    }
}
