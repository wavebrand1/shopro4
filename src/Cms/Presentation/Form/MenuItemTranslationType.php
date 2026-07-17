<?php
declare(strict_types=1);
namespace App\Cms\Presentation\Form;
use App\Cms\Domain\Entity\MenuItemTranslation;use Symfony\Component\Form\AbstractType;use Symfony\Component\Form\Extension\Core\Type\TextType;use Symfony\Component\Form\FormBuilderInterface;use Symfony\Component\OptionsResolver\OptionsResolver;
final class MenuItemTranslationType extends AbstractType
{
 public function buildForm(FormBuilderInterface $builder,array $options):void{$builder->add('name',TextType::class,['label'=>'Nazwa'])->add('caption',TextType::class,['label'=>'Podpis','required'=>false])->add('link',TextType::class,['label'=>'Link własny','required'=>false,'help'=>'Używany tylko dla pozycji typu „Link zewnętrzny”. Link do podstrony otrzymuje slug z tłumaczenia podstrony.']);}
 public function configureOptions(OptionsResolver $resolver):void{$resolver->setDefaults(['data_class'=>MenuItemTranslation::class]);}
}
