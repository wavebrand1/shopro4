<?php

declare(strict_types=1);

namespace App\Tests\Unit\Cms;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

final class StructuredDataPaginationCollisionTest extends TestCase
{
    public function testPaginationNumberIsNotTreatedAsCmsPage(): void
    {
        $template = file_get_contents(dirname(__DIR__, 3).'/templates/cms/seo/_structured_data.html.twig');
        self::assertNotFalse($template);
        $twig = new Environment(new ArrayLoader(['structured' => $template]));
        $twig->addGlobal('app', (object) [
            'request' => (object) ['pathInfo' => '/produkty', 'locale' => 'pl'],
        ]);
        $twig->addFunction(new TwigFunction('path', static fn (): string => '/'));
        $twig->addFunction(new TwigFunction('absolute_url', static fn (string $path): string => 'https://example.test'.$path));
        $twig->addFunction(new TwigFunction('shopro_setting', static fn (string $key, string $default = ''): string => $default));
        $twig->addFunction(new TwigFunction('shopro_language', static fn (): null => null));
        $twig->addFunction(new TwigFunction('shopro_trans', static fn (): string => 'Strona główna'));

        $html = $twig->render('structured', ['page' => 1]);

        self::assertStringContainsString('application/ld+json', $html);
        self::assertStringContainsString('https:\/\/example.test\/produkty', $html);
    }
}
