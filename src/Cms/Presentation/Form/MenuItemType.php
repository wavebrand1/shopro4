<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Form;

use App\Cms\Domain\Entity\MenuItem;
use App\Cms\Domain\Entity\Page;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MenuItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nazwa'])
            ->add('caption', TextType::class, ['label' => 'Podpis', 'required' => false])
            ->add('parent', EntityType::class, ['class' => MenuItem::class, 'choice_label' => 'name', 'label' => 'Pozycja nadrzędna', 'placeholder' => 'Poziom główny', 'required' => false])
            ->add('contentType', ChoiceType::class, ['label' => 'Typ', 'choices' => ['Podstrona' => MenuItem::TYPE_PAGE, 'Link zewnętrzny' => MenuItem::TYPE_WEB, 'Element bez linku' => MenuItem::TYPE_PLACEHOLDER]])
            ->add('page', EntityType::class, ['class' => Page::class, 'choice_label' => 'title', 'label' => 'Podstrona', 'placeholder' => 'Wybierz podstronę', 'required' => false])
            ->add('link', TextType::class, ['label' => 'Link zewnętrzny', 'help' => 'Np. https://example.com lub /#kontakt', 'required' => false])
            ->add('target', ChoiceType::class, ['label' => 'Otwieranie linku', 'choices' => ['W tej samej karcie' => '_self', 'W nowej karcie' => '_blank']])
            ->add('place', ChoiceType::class, ['label' => 'Miejsce menu', 'choices' => ['Menu górne' => MenuItem::PLACE_HEADER, 'Menu dolne' => MenuItem::PLACE_FOOTER]])
            ->add('homePage', CheckboxType::class, ['label' => 'Link do strony głównej', 'required' => false])
            ->add('active', CheckboxType::class, ['label' => 'Aktywna', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MenuItem::class]);
    }
}
