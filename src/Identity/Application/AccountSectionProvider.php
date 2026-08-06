<?php
declare(strict_types=1);
namespace App\Identity\Application;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
#[AutoconfigureTag('shopro.account_section_provider')]
interface AccountSectionProvider
{
 /** @return list<array{label:string,description:string,route:string}> */public function sections():array;
}
