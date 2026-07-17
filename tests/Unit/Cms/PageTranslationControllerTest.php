<?php
declare(strict_types=1);
namespace App\Tests\Unit\Cms;

use App\Cms\Presentation\Http\Admin\PageTranslationController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

final class PageTranslationControllerTest extends TestCase
{
 public function testBuilderSanitizerAcceptsColumnSettingsAndNestedComponents():void
 {
  $sanitizer=$this->createMock(HtmlSanitizerInterface::class);
  $sanitizer->expects(self::once())->method('sanitize')->willReturn('<p>Bezpieczna treść</p>');
  $controller=new PageTranslationController($sanitizer);
  $method=new \ReflectionMethod($controller,'sanitizeBuilder');
  $json='[{"type":"layout_section","data":{"columns":[{"width":70,"components":[1,2]},[{"type":"rich_text","data":{"content":"<script>x</script>"}}]],"widths":[70,30]}}]';

  $result=json_decode($method->invoke($controller,$json),true,64,JSON_THROW_ON_ERROR);

  self::assertSame(70,$result[0]['data']['columns'][0]['width']);
  self::assertSame('<p>Bezpieczna treść</p>',$result[0]['data']['columns'][1][0]['data']['content']);
 }
}
