<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Form;

use App\Identity\Domain\Entity\SiteUser;
use App\Language\Application\SystemTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class SiteRegistrationType extends AbstractType
{
    public function __construct(private readonly SystemTranslator $translator) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $t = $this->translator->translate(...);
        $builder
            ->add('username', TextType::class, ['label' => $t('site_users.login')])
            ->add('email', EmailType::class, ['label' => $t('site_users.email')])
            ->add('plainPassword', RepeatedType::class, ['type' => PasswordType::class, 'mapped' => false, 'invalid_message' => $t('site_registration.password_mismatch'), 'first_options' => ['label' => $t('site_users.password')], 'second_options' => ['label' => $t('site_registration.repeat_password')], 'constraints' => [new NotBlank(), new Length(min: 12)]])
            ->add('newsletter', CheckboxType::class, ['label' => $t('site_registration.newsletter'), 'required' => false])
            ->add('termsAccepted', CheckboxType::class, ['label' => $t('site_registration.accept_terms'), 'mapped' => false, 'constraints' => [new IsTrue(message: $t('site_registration.terms_required'))]]);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => SiteUser::class]); }
}
