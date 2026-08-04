<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Cms\Application\PageBuilderSanitizer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PageBuilderHtmlEmbedTest extends KernelTestCase
{
    public function testGoogleMapsIframeSurvivesPageBuilderSanitization(): void
    {
        self::bootKernel();
        $sanitizer = self::getContainer()->get(PageBuilderSanitizer::class);
        $iframe = '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1078.9926929752442!2d20.361886855159106!3d49.84618051219221" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>';
        $data = [[
            'type' => 'layout_section',
            'data' => ['columns' => [[[
                'type' => 'rich_text',
                'data' => ['content' => $iframe],
            ]]]],
        ]];
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        $sanitized = json_decode($sanitizer->sanitize($json), true, 64, JSON_THROW_ON_ERROR);
        $content = $sanitized[0]['data']['columns'][0][0]['data']['content'];

        self::assertStringContainsString('<iframe', $content);
        self::assertStringContainsString('https://www.google.com/maps/embed?', $content);
        self::assertStringContainsString('allowfullscreen', $content);
        self::assertStringNotContainsString('style=', $content);
    }
}
