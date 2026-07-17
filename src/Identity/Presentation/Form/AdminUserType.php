<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Form;

use App\Identity\Domain\Entity\AdminUser;
use App\Language\Application\SystemTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class AdminUserType extends AbstractType
{
    public function __construct(private readonly SystemTranslator $translator) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $new = null === $options['data']->getId();
        $t = $this->translator->translate(...);
        $builder
            ->add('username', TextType::class, ['label' => $t('users.login')])
            ->add('email', EmailType::class, ['label' => $t('users.email')])
            ->add('firstName', TextType::class, ['label' => $t('users.first_name'), 'required' => false])
            ->add('lastName', TextType::class, ['label' => $t('users.last_name'), 'required' => false])
            ->add('plainPassword', PasswordType::class, ['label' => $t($new ? 'users.password' : 'users.new_password'), 'mapped' => false, 'required' => $new, 'constraints' => $new ? [new NotBlank(), new Length(min: 12)] : [new Length(min: 12)]])
            ->add('active', CheckboxType::class, ['label' => $t('users.account_active'), 'required' => false])
            ->add('newsletter', CheckboxType::class, ['label' => $t('users.newsletter_subscribed'), 'required' => false])
            ->add('apiEnabled', CheckboxType::class, ['label' => $t('users.api_active'), 'required' => false])
            ->add('apiScopes', ChoiceType::class, ['label' => $t('users.api_permissions'), 'expanded' => true, 'multiple' => true, 'choices' => [$t('users.api_read') => 'read', $t('users.api_write') => 'write', $t('users.api_cms') => 'cms']]);
    }

    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => AdminUser::class]); }
}
