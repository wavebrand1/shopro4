<?php
declare(strict_types=1);

namespace App\Cms\Presentation\Form;

use App\Cms\Domain\Entity\PageTranslation;
use App\Cms\Domain\PageBuilderData;
use App\Language\Application\SystemTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class PageTranslationType extends AbstractType
{
    public function __construct(private readonly SystemTranslator $translator) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $t = $this->translator->translate(...);
        $builder
            ->add('title', TextType::class, ['label' => $t('page.title'), 'constraints' => [new Assert\NotBlank()]])
            ->add('slug', TextType::class, ['label' => 'Slug', 'constraints' => [new Assert\Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]])
            ->add('caption', TextareaType::class, ['label' => $t('page.caption'), 'required' => false])
            ->add('seoTitle', TextType::class, ['label' => $t('page.seo_title'), 'required' => false])
            ->add('description', TextareaType::class, ['label' => $t('page.meta_description'), 'required' => false])
            ->add('canonical', TextType::class, ['label' => $t('page.canonical'), 'required' => false])
            ->add('content', HiddenType::class, ['required' => false])
            ->add('builderData', HiddenType::class, ['required' => false, 'constraints' => [new Assert\Callback([PageBuilderData::class, 'validate'])]])
            ->add('builderCss', HiddenType::class, ['required' => false])
            ->add('published', CheckboxType::class, ['label' => $t('page.translation_published'), 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => PageTranslation::class]); }
}
