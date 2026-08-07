<?php
declare(strict_types=1);
namespace App\Tests\Unit\Cms;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class LanguageSwitcherPaginationCollisionTest extends TestCase
{
    public function testNumericPaginationVariableIsNotTreatedAsCmsPage(): void
    {
        $twig = new Environment(new FilesystemLoader(dirname(__DIR__, 3).'/templates'));
        $twig->addFunction(new TwigFunction('shopro_language', static fn(): ?object => null));
        $twig->addFunction(new TwigFunction('shopro_languages', static fn(): array => []));
        $twig->addFunction(new TwigFunction('shopro_trans', static fn(string $key): string => $key));
        $twig->addFunction(new TwigFunction('path', static fn(): string => '/language'));

        $html = $twig->render('cms/_language_switcher.html.twig', ['page' => 1]);

        self::assertStringContainsString('site-language-picker', $html);
    }
}
