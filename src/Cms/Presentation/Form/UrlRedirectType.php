<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Form;

use App\Cms\Domain\Entity\UrlRedirect;
use App\Language\Application\SystemTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UrlRedirectType extends AbstractType
{
    public function __construct(private readonly SystemTranslator $translator) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('sourcePath', TextType::class, ['label' => $this->translator->translate('redirect.source'), 'help' => $this->translator->translate('redirect.source_help')])
            ->add('targetPath', TextType::class, ['label' => $this->translator->translate('redirect.target'), 'help' => $this->translator->translate('redirect.target_help')])
            ->add('permanent', CheckboxType::class, ['label' => $this->translator->translate('redirect.permanent'), 'required' => false])
            ->add('active', CheckboxType::class, ['label' => $this->translator->translate('common.active'), 'required' => false]);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => UrlRedirect::class]); }
}
