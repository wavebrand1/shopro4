<?php
declare(strict_types=1);
namespace App\Language\Presentation\Http;

use App\Language\Domain\Entity\Language;
use App\Module\Application\ModuleAvailability;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(event:'kernel.request', priority:18)]
final class LanguageContextSubscriber
{
 public function __construct(private readonly EntityManagerInterface $em,private readonly ModuleAvailability $modules){}
 public function __invoke(RequestEvent $event):void{
  if(!$event->isMainRequest())return;$request=$event->getRequest();
  if($request->getPathInfo()==='/install'||str_starts_with($request->getPathInfo(),'/install/'))return;
  if(!$this->modules->isEnabled('language'))return;
  try{
   $code=mb_strtolower((string)($request->attributes->get('_locale')?:$request->query->get('lang')?:($request->hasSession()?$request->getSession()->get('shopro_language'):'')));
   $language=$code!==''?$this->em->getRepository(Language::class)->findOneBy(['code'=>$code,'active'=>true]):null;
   $language??=$this->em->getRepository(Language::class)->findOneBy(['defaultLanguage'=>true,'active'=>true]);
   if(!$language)return;
   $request->setLocale($language->getCode());
   $request->attributes->set('_shopro_language',$language);
   if($request->hasSession())$request->getSession()->set('shopro_language',$language->getCode());
  }catch(\Throwable){}
 }
}
