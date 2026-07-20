<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Form;

use App\Language\Application\SystemTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class SitePasswordChangeType extends AbstractType
{
    public function __construct(private readonly SystemTranslator $translator) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $t = $this->translator->translate(...);
        $builder
            ->add('currentPassword', PasswordType::class, ['label' => $t('site_account.current_password'), 'mapped' => false, 'constraints' => [new NotBlank()]])
            ->add('newPassword', RepeatedType::class, ['type' => PasswordType::class, 'mapped' => false, 'invalid_message' => $t('auth.reset_password_mismatch'), 'first_options' => ['label' => $t('auth.new_password')], 'second_options' => ['label' => $t('auth.repeat_password')], 'constraints' => [new NotBlank(), new Length(min: 12)]]);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => null]); }
}
