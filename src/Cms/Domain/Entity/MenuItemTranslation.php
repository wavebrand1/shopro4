<?php
declare(strict_types=1);
namespace App\Cms\Domain\Entity;

use App\Cms\Domain\MenuLink;
use App\Language\Domain\Entity\Language;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name:'cms_menu_item_translation')]
#[ORM\UniqueConstraint(name:'uniq_menu_item_language',columns:['menu_item_id','language_id'])]
class MenuItemTranslation
{
 #[ORM\Id,ORM\GeneratedValue,ORM\Column]private ?int $id=null;
 #[ORM\ManyToOne,ORM\JoinColumn(nullable:false,onDelete:'CASCADE')]private MenuItem $menuItem;
 #[ORM\ManyToOne,ORM\JoinColumn(nullable:false,onDelete:'CASCADE')]private Language $language;
 #[ORM\Column(length:120)]#[Assert\NotBlank,Assert\Length(max:120)]private string $name='';
 #[ORM\Column(length:200,nullable:true)]#[Assert\Length(max:200)]private ?string $caption=null;
 #[ORM\Column(length:500,nullable:true)]#[Assert\Length(max:500)]#[Assert\Callback([self::class,'validateLink'])]private ?string $link=null;
 public function __construct(MenuItem $menuItem,Language $language){$this->menuItem=$menuItem;$this->language=$language;$this->name=$menuItem->getName();$this->caption=$menuItem->getCaption();$this->link=$menuItem->getLink();}
 public function getId():?int{return $this->id;}public function getMenuItem():MenuItem{return $this->menuItem;}public function getLanguage():Language{return $this->language;}
 public function getName():string{return $this->name;}public function setName(string $value):void{$this->name=trim($value);}public function getCaption():?string{return $this->caption;}public function setCaption(?string $value):void{$value=trim((string)$value);$this->caption=$value!==''?$value:null;}public function getLink():?string{return $this->link;}public function setLink(?string $value):void{$value=trim((string)$value);$this->link=$value!==''?$value:null;}
 public static function validateLink(mixed $value,\Symfony\Component\Validator\Context\ExecutionContextInterface $context):void{if($value!==null&&!MenuLink::isSafe((string)$value))$context->buildViolation('validation.menu.link_invalid')->addViolation();}
}
