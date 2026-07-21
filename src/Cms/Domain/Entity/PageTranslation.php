<?php
declare(strict_types=1);
namespace App\Cms\Domain\Entity;

use App\Language\Domain\Entity\Language;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name:'cms_page_translation')]
#[ORM\UniqueConstraint(name:'uniq_page_language',columns:['page_id','language_id'])]
#[ORM\UniqueConstraint(name:'uniq_language_slug',columns:['language_id','slug'])]
class PageTranslation
{
 #[ORM\Id,ORM\GeneratedValue,ORM\Column]private ?int $id=null;
 #[ORM\ManyToOne,ORM\JoinColumn(nullable:false,onDelete:'CASCADE')]private Page $page;
 #[ORM\ManyToOne,ORM\JoinColumn(nullable:false,onDelete:'CASCADE')]private Language $language;
 #[ORM\Column(length:200)]private string $title='';
 #[ORM\Column(length:180)]private string $slug='';
 #[ORM\Column(length:600)]private string $caption='';
 #[ORM\Column(length:200)]private string $seoTitle='';
 #[ORM\Column(type:Types::TEXT)]private string $description='';
 #[ORM\Column(type:Types::TEXT)]private string $content='';
 #[ORM\Column(type:Types::TEXT)]private string $builderData='';
 #[ORM\Column(type:Types::TEXT)]private string $builderCss='';
 #[ORM\Column(length:300),Assert\Length(max:300),Assert\Url(protocols:['http','https'],requireTld:true,message:'validation.page.canonical_url')]private string $canonical='';
 #[ORM\Column(options:['default'=>false])]private bool $published=false;
 public function __construct(Page $page,Language $language){$this->page=$page;$this->language=$language;$this->title=$page->getTitle();$this->slug=$page->getSlug();$this->caption=$page->getCaption();$this->seoTitle=$page->getSeoTitle();$this->description=$page->getDescription();$this->content=$page->getContent();$this->builderData=$page->getBuilderData();$this->builderCss=$page->getBuilderCss();$this->canonical=$page->getCanonical();}
 public function getId():?int{return $this->id;} public function getPage():Page{return $this->page;} public function getLanguage():Language{return $this->language;}
 public function getTitle():string{return $this->title;}public function setTitle(string $v):void{$this->title=trim($v);} public function getSlug():string{return $this->slug;}public function setSlug(string $v):void{$this->slug=mb_strtolower(trim($v));}
 public function getCaption():string{return $this->caption;}public function setCaption(?string $v):void{$this->caption=trim($v??'');} public function getSeoTitle():string{return $this->seoTitle;}public function setSeoTitle(?string $v):void{$this->seoTitle=trim($v??'');}
 public function getDescription():string{return $this->description;}public function setDescription(?string $v):void{$this->description=trim($v??'');} public function getContent():string{return $this->content;}public function setContent(?string $v):void{$this->content=$v??'';}
 public function getBuilderData():string{return $this->builderData;}public function setBuilderData(?string $v):void{$this->builderData=$v??'';} public function getBuilderCss():string{return $this->builderCss;}public function setBuilderCss(?string $v):void{$this->builderCss=$v??'';}
 public function getCanonical():string{return $this->canonical;}public function setCanonical(?string $v):void{$this->canonical=trim($v??'');} public function isPublished():bool{return $this->published;}public function setPublished(bool $v):void{$this->published=$v;}
 public function getEffectiveSeoTitle():string{return $this->seoTitle?:$this->title;} public function usesComponentBuilder():bool{return true;} public function isHomePage():bool{return $this->page->isHomePage();}
 public function getBuilderBlocks():array{try{$v=json_decode($this->builderData,true,64,JSON_THROW_ON_ERROR);return is_array($v)?$v:[];}catch(\JsonException){return [];}}
 public function getKeywords():string{return '';}public function isFollow():bool{return true;}public function getMeta():string{return '';}public function getJavascript():string{return '';}
}
