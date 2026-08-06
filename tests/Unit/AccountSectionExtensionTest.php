<?php
declare(strict_types=1);
namespace App\Tests\Unit;
use App\Identity\Application\AccountSectionProvider;use App\Identity\Presentation\Twig\AccountSectionExtension;use PHPUnit\Framework\TestCase;
final class AccountSectionExtensionTest extends TestCase
{
 public function testItCollectsValidSectionsAndIgnoresIncompleteOnes():void{$provider=new class implements AccountSectionProvider{public function sections():array{return [['label'=>'Zamówienia','description'=>'Historia','route'=>'shop_orders'],['label'=>'','description'=>'Ukryta','route'=>'invalid']];}};$sections=(new AccountSectionExtension([$provider]))->sections();self::assertSame([['label'=>'Zamówienia','description'=>'Historia','route'=>'shop_orders']],$sections);}
}
