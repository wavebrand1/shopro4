<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Form;

use App\Identity\Domain\Entity\Membership;
use App\Identity\Domain\Entity\SiteUser;
use App\Identity\Infrastructure\Persistence\Doctrine\MembershipRepository;
use App\Language\Application\SystemTranslator;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class SiteUserType extends AbstractType
{
    public function __construct(private readonly SystemTranslator $translator) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $new = null === $options['data']->getId();
        $t = $this->translator->translate(...);
        $builder
            ->add('username', TextType::class, ['label' => $t('site_users.login')])
            ->add('email', EmailType::class, ['label' => $t('site_users.email')])
            ->add('plainPassword', PasswordType::class, ['label' => $t($new ? 'site_users.password' : 'site_users.new_password'), 'mapped' => false, 'required' => $new, 'constraints' => $new ? [new NotBlank(), new Length(min: 12)] : [new Length(min: 12)]])
            ->add('active', CheckboxType::class, ['label' => $t('site_users.active'), 'required' => false])
            ->add('memberships', EntityType::class, ['label' => $t('site_users.memberships'), 'help' => $t('site_users.memberships_help'), 'class' => Membership::class, 'choice_label' => 'title', 'query_builder' => static fn (MembershipRepository $repository) => $repository->createQueryBuilder('membership')->orderBy('membership.title', 'ASC'), 'multiple' => true, 'required' => false, 'attr' => ['size' => 7]]);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => SiteUser::class]); }
}
