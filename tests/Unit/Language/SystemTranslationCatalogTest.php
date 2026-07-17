<?php
declare(strict_types=1);
namespace App\Tests\Unit\Language;

use App\Language\Application\SystemTranslationCatalog;
use PHPUnit\Framework\TestCase;

final class SystemTranslationCatalogTest extends TestCase
{
 public function testEverySystemPhraseHasPolishAndEnglishTranslation():void
 {
  self::assertNotEmpty(SystemTranslationCatalog::phrases());
  foreach(SystemTranslationCatalog::phrases() as $key=>$translations){
   self::assertNotSame('',trim($key));
   self::assertArrayHasKey('pl',$translations,sprintf('Brak polskiego tłumaczenia dla "%s".',$key));
   self::assertArrayHasKey('en',$translations,sprintf('Brak angielskiego tłumaczenia dla "%s".',$key));
   self::assertNotSame('',trim($translations['pl']),sprintf('Puste polskie tłumaczenie dla "%s".',$key));
   self::assertNotSame('',trim($translations['en']),sprintf('Puste angielskie tłumaczenie dla "%s".',$key));
  }
 }
}
