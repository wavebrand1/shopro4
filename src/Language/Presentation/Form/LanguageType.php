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

final class LanguageType extends AbstractType
{
 public function buildForm(FormBuilderInterface $b,array $o):void{$b
  ->add('name',TextType::class,['label'=>'Nazwa języka','constraints'=>[new Assert\NotBlank()]])
  ->add('code',TextType::class,['label'=>'Kod / flaga','help'=>'Dwuliterowy kod ISO, np. pl, en, de.','constraints'=>[new Assert\Regex('/^[a-zA-Z]{2}$/')]])
  ->add('direction',ChoiceType::class,['label'=>'Kierunek tekstu','choices'=>['Od lewej do prawej (LTR)'=>'ltr','Od prawej do lewej (RTL)'=>'rtl'],'expanded'=>true])
  ->add('author',TextType::class,['label'=>'Autor tłumaczenia','required'=>false])
  ->add('subdomain',TextType::class,['label'=>'Subdomena / adres języka','required'=>false,'help'=>'Opcjonalny pełny adres, np. https://en.example.pl'])
  ->add('locale',TextType::class,['label'=>'Locale','help'=>'Np. pl_PL, en_GB lub de_DE.'])
  ->add('currency',TextType::class,['label'=>'Waluta','constraints'=>[new Assert\Regex('/^[A-Za-z]{3}$/')]])
  ->add('currencySymbol',TextType::class,['label'=>'Symbol waluty'])
  ->add('decimalSeparator',TextType::class,['label'=>'Separator dziesiętny'])
  ->add('thousandsSeparator',TextType::class,['label'=>'Separator tysięcy'])
  ->add('active',CheckboxType::class,['label'=>'Język aktywny','required'=>false])
  ->add('defaultLanguage',CheckboxType::class,['label'=>'Język domyślny','required'=>false]);}
 public function configureOptions(OptionsResolver $r):void{$r->setDefaults(['data_class'=>Language::class]);}
}
