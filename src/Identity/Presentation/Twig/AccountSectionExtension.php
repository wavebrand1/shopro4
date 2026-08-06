<?php
declare(strict_types=1);
namespace App\Identity\Presentation\Twig;
use App\Identity\Application\AccountSectionProvider;use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;use Twig\Extension\AbstractExtension;use Twig\TwigFunction;
final class AccountSectionExtension extends AbstractExtension
{
 /** @param iterable<AccountSectionProvider> $providers */public function __construct(#[AutowireIterator('shopro.account_section_provider')]private readonly iterable $providers){}
 public function getFunctions():array{return [new TwigFunction('shopro_account_sections',[$this,'sections'])];}
 /** @return list<array{label:string,description:string,route:string}> */public function sections():array{$sections=[];foreach($this->providers as $provider)foreach($provider->sections() as $section)if(($section['label']??'')!==''&&($section['route']??'')!=='')$sections[]=$section;return $sections;}
}
