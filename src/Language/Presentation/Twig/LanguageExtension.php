<?php
declare(strict_types=1);
namespace App\Language\Presentation\Twig;

use App\Language\Domain\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LanguageExtension extends AbstractExtension
{
 public function __construct(private readonly EntityManagerInterface $em,private readonly RequestStack $requests){}
 public function getFunctions():array{return [new TwigFunction('shopro_languages',$this->languages(...)),new TwigFunction('shopro_language',$this->current(...)),new TwigFunction('shopro_default_language',$this->defaultLanguage(...))];}
 public function languages():array{try{return $this->em->getRepository(Language::class)->findBy(['active'=>true],['name'=>'ASC']);}catch(\Throwable){return [];}}
 public function current():?Language{return $this->requests->getCurrentRequest()?->attributes->get('_shopro_language');}
 public function defaultLanguage():?Language{try{return $this->em->getRepository(Language::class)->findOneBy(['defaultLanguage'=>true]);}catch(\Throwable){return null;}}
}
