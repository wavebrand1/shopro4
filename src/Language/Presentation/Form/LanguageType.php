<?php
declare(strict_types=1);
namespace App\Language\Presentation\Form;

use App\Language\Domain\Entity\Language;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Locales;

final class LanguageType extends AbstractType
{
 public function buildForm(FormBuilderInterface $b,array $o):void{$b
  ->add('name',TextType::class,['label'=>'Nazwa języka','constraints'=>[new Assert\NotBlank()]])
  ->add('code',TextType::class,['label'=>'Kod / flaga','help'=>'Dwuliterowy kod ISO, np. pl, en, de.','constraints'=>[new Assert\Regex('/^[a-zA-Z]{2}$/')]])
  ->add('direction',ChoiceType::class,['label'=>'Kierunek tekstu','choices'=>['Od lewej do prawej (LTR)'=>'ltr','Od prawej do lewej (RTL)'=>'rtl'],'expanded'=>true])
  ->add('author',TextType::class,['label'=>'Autor tłumaczenia','required'=>false])
  ->add('subdomain',TextType::class,['label'=>'Subdomena / adres języka','required'=>false,'help'=>'Opcjonalny pełny adres, np. https://en.example.pl'])
  ->add('locale',ChoiceType::class,['label'=>'Locale','choices'=>array_flip(Locales::getNames('pl')),'choice_translation_domain'=>false])
  ->add('currency',ChoiceType::class,['label'=>'Waluta','choices'=>array_flip(Currencies::getNames('pl')),'choice_translation_domain'=>false])
  ->add('currencySymbol',ChoiceType::class,['label'=>'Symbol waluty','choices'=>['zł'=>'zł','€'=>'€','$'=>'$','£'=>'£','Fr'=>'Fr','Kč'=>'Kč','kr'=>'kr','¥'=>'¥','د.إ'=>'د.إ','Brak symbolu'=>'']])
  ->add('decimalSeparator',ChoiceType::class,['label'=>'Separator dziesiętny','choices'=>['Przecinek (,)'=>',','Kropka (.)'=>'.']])
  ->add('thousandsSeparator',ChoiceType::class,['label'=>'Separator tysięcy','choices'=>['Spacja'=>' ','Kropka (.)'=>'.','Przecinek (,)'=>',','Bez separatora'=>'']])
  ->add('active',CheckboxType::class,['label'=>'Język aktywny','required'=>false])
  ->add('defaultLanguage',CheckboxType::class,['label'=>'Język domyślny','required'=>false]);}
 public function configureOptions(OptionsResolver $r):void{$r->setDefaults(['data_class'=>Language::class]);}
}
