<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Form;

use App\Identity\Domain\Entity\Membership;
use App\Language\Application\SystemTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MembershipType extends AbstractType
{
    public function __construct(private readonly SystemTranslator $translator) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => $this->translator->translate('membership.name')])
            ->add('description', TextareaType::class, ['label' => $this->translator->translate('membership.description'), 'required' => false, 'attr' => ['rows' => 8]])
            ->add('active', CheckboxType::class, ['label' => $this->translator->translate('membership.active'), 'required' => false]);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => Membership::class]); }
}
