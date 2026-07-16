<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Form;

use App\Identity\Domain\Entity\AdminUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $new = null === $options['data']->getId();
        $builder
            ->add('username', TextType::class, ['label' => 'Login'])
            ->add('email', EmailType::class, ['label' => 'Adres e-mail'])
            ->add('firstName', TextType::class, ['label' => 'Imię', 'required' => false])
            ->add('lastName', TextType::class, ['label' => 'Nazwisko', 'required' => false])
            ->add('plainPassword', PasswordType::class, ['label' => $new ? 'Hasło' : 'Nowe hasło', 'mapped' => false, 'required' => $new, 'constraints' => $new ? [new NotBlank(), new Length(min: 12)] : [new Length(min: 12)]])
            ->add('active', CheckboxType::class, ['label' => 'Konto aktywne', 'required' => false])
            ->add('newsletter', CheckboxType::class, ['label' => 'Zapisany do newslettera', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => AdminUser::class]); }
}
