<?php
declare(strict_types=1);
namespace App\Language\Presentation\Twig;

use App\Language\Application\SystemTranslationCatalog;
use App\Language\Domain\Entity\Language;
use App\Language\Domain\Entity\Translation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LanguageExtension extends AbstractExtension
{
 /** @var array<string,string> */
 private array $translationCache=[];

 public function __construct(private readonly EntityManagerInterface $em,private readonly RequestStack $requests){}
 public function getFunctions():array{return [new TwigFunction('shopro_languages',$this->languages(...)),new TwigFunction('shopro_language',$this->current(...)),new TwigFunction('shopro_default_language',$this->defaultLanguage(...)),new TwigFunction('shopro_trans',$this->translate(...))];}
 public function languages():array{try{return $this->em->getRepository(Language::class)->findBy(['active'=>true],['name'=>'ASC']);}catch(\Throwable){return [];}}
 public function current():?Language{return $this->requests->getCurrentRequest()?->attributes->get('_shopro_language');}
 public function defaultLanguage():?Language{try{return $this->em->getRepository(Language::class)->findOneBy(['defaultLanguage'=>true]);}catch(\Throwable){return null;}}
 public function translate(string $key):string
 {
  $language=$this->current()??$this->defaultLanguage();
  $code=$language?->getCode()??'pl';
  $cacheKey=$code.':'.$key;
  if(isset($this->translationCache[$cacheKey]))return $this->translationCache[$cacheKey];
  try{
   $translation=$language?$this->em->getRepository(Translation::class)->findOneBy(['language'=>$language,'key'=>$key]):null;
   if($translation&&trim($translation->getValue())!=='')return $this->translationCache[$cacheKey]=$translation->getValue();
  }catch(\Throwable){}
  $phrase=SystemTranslationCatalog::phrases()[$key]??null;
  return $this->translationCache[$cacheKey]=$phrase[$code]??$phrase['pl']??$key;
 }
}
