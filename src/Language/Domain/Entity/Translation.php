<?php
declare(strict_types=1);
namespace App\Language\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'language_translation')]
#[ORM\UniqueConstraint(name: 'uniq_language_translation_key', columns: ['language_id','translation_key'])]
class Translation
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable:false,onDelete:'CASCADE')] private Language $language;
    #[ORM\Column(name:'translation_key', length:190)] private string $key;
    #[ORM\Column(type:Types::TEXT)] private string $value='';
    public function __construct(Language $language,string $key){$this->language=$language;$this->key=trim($key);}
    public function getId(): ?int{return $this->id;}
    public function getLanguage(): Language{return $this->language;}
    public function getKey(): string{return $this->key;}
    public function getValue(): string{return $this->value;}
    public function setValue(string $value): void{$this->value=$value;}
}
