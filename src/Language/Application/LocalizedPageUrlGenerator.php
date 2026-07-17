<?php
declare(strict_types=1);
namespace App\Language\Application;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\PageTranslation;
use App\Language\Domain\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class LocalizedPageUrlGenerator
{
 public function __construct(private readonly EntityManagerInterface $em,private readonly UrlGeneratorInterface $urls){}

 public function page(Page $page,Language $language):string
 {
  if($language->isDefaultLanguage())return $page->isHomePage()?$this->urls->generate('app_home'):$this->urls->generate('cms_page_show',['slug'=>$page->getSlug()]);
  $translation=$this->em->getRepository(PageTranslation::class)->findOneBy(['page'=>$page,'language'=>$language,'published'=>true]);
  if(!$translation)return $page->isHomePage()?$this->urls->generate('app_home'):$this->urls->generate('cms_page_show',['slug'=>$page->getSlug()]);
  return $this->urls->generate('cms_page_show_localized',['_locale'=>$language->getCode(),'slug'=>$translation->getSlug()]);
 }
}
