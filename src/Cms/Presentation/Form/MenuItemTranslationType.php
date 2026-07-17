<?php
declare(strict_types=1);
namespace App\Cms\Presentation\Form;
use App\Language\Application\SystemTranslator;
use App\Cms\Domain\Entity\MenuItemTranslation;use Symfony\Component\Form\AbstractType;use Symfony\Component\Form\Extension\Core\Type\TextType;use Symfony\Component\Form\FormBuilderInterface;use Symfony\Component\OptionsResolver\OptionsResolver;
final class MenuItemTranslationType extends AbstractType
{
 public function __construct(private readonly SystemTranslator $translator){}
 public function buildForm(FormBuilderInterface $builder,array $options):void{$t=$this->translator->translate(...);$builder->add('name',TextType::class,['label'=>$t('menu.name')])->add('caption',TextType::class,['label'=>$t('menu.caption'),'required'=>false])->add('link',TextType::class,['label'=>$t('menu.custom_link'),'required'=>false,'help'=>$t('menu.translation_link_help')]);}
 public function configureOptions(OptionsResolver $resolver):void{$resolver->setDefaults(['data_class'=>MenuItemTranslation::class]);}
}
