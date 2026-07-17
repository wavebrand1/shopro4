<?php
declare(strict_types=1);
namespace App\Language\Presentation\Form;

use App\Language\Domain\Entity\Language;
use App\Language\Application\SystemTranslator;
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
 public function __construct(private readonly SystemTranslator $translator){}
 public function buildForm(FormBuilderInterface $b,array $o):void{$t=$this->translator->translate(...);$locale=$this->translator->locale();$b
  ->add('name',TextType::class,['label'=>$t('language.name'),'constraints'=>[new Assert\NotBlank()]])
  ->add('code',TextType::class,['label'=>$t('language.code'),'help'=>$t('language.code_help'),'constraints'=>[new Assert\Regex('/^[a-zA-Z]{2}$/')]])
  ->add('direction',ChoiceType::class,['label'=>$t('language.direction'),'choices'=>[$t('language.ltr')=>'ltr',$t('language.rtl')=>'rtl'],'expanded'=>true])
  ->add('author',TextType::class,['label'=>$t('language.author'),'required'=>false])
  ->add('subdomain',TextType::class,['label'=>$t('language.subdomain'),'required'=>false,'help'=>$t('language.subdomain_help')])
  ->add('locale',ChoiceType::class,['label'=>$t('language.locale'),'choices'=>array_flip(Locales::getNames($locale)),'choice_translation_domain'=>false])
  ->add('currency',ChoiceType::class,['label'=>$t('language.currency'),'choices'=>array_flip(Currencies::getNames($locale)),'choice_translation_domain'=>false])
  ->add('currencySymbol',ChoiceType::class,['label'=>$t('language.currency_symbol'),'choices'=>['zł'=>'zł','€'=>'€','$'=>'$','£'=>'£','Fr'=>'Fr','Kč'=>'Kč','kr'=>'kr','¥'=>'¥','د.إ'=>'د.إ',$t('language.no_symbol')=>'']])
  ->add('decimalSeparator',ChoiceType::class,['label'=>$t('language.decimal_separator'),'choices'=>[$t('language.comma')=>',',$t('language.dot')=>'.']])
  ->add('thousandsSeparator',ChoiceType::class,['label'=>$t('language.thousands_separator'),'choices'=>[$t('language.space')=>' ',$t('language.dot')=>'.',$t('language.comma')=>',',$t('language.no_separator')=>'']])
  ->add('active',CheckboxType::class,['label'=>$t('language.active'),'required'=>false])
  ->add('defaultLanguage',CheckboxType::class,['label'=>$t('language.default'),'required'=>false]);}
 public function configureOptions(OptionsResolver $r):void{$r->setDefaults(['data_class'=>Language::class]);}
}
