<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Form;

use App\Identity\Domain\Entity\SiteUser;
use App\Language\Application\SystemTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SiteProfileType extends AbstractType
{
    public function __construct(private readonly SystemTranslator $translator) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, ['label' => $this->translator->translate('site_users.login')])
            ->add('email', EmailType::class, ['label' => $this->translator->translate('site_users.email')]);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => SiteUser::class]); }
}
