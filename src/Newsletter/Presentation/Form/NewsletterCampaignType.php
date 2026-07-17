<?php

declare(strict_types=1);

namespace App\Newsletter\Presentation\Form;

use App\Newsletter\Domain\Entity\NewsletterCampaign;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class NewsletterCampaignType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void { $builder->add('subject', TextType::class, ['label' => 'Temat', 'constraints' => [new NotBlank()]])->add('content', TextareaType::class, ['label' => 'Treść HTML', 'attr' => ['rows' => 16, 'data-rich-editor' => true], 'constraints' => [new NotBlank()]]); }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => NewsletterCampaign::class]); }
}
