<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Cms\Presentation\Twig\MenuExtension;
use App\Cms\Infrastructure\Persistence\Doctrine\MenuItemRepository;
use App\Language\Application\LocalizedPageUrlGenerator;
use App\Language\Application\SystemTranslator;
use App\Language\Presentation\Twig\LanguageExtension;
use App\Media\Presentation\Twig\ResponsiveImageExtension;
use App\Module\Application\ModuleAvailability;
use App\Settings\Application\SettingsProvider;
use App\Settings\Presentation\Twig\SettingsExtension;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TwigModuleRuntimeGuardTest extends TestCase
{
    private ModuleAvailability $disabledModules;

    protected function setUp(): void
    {
        $this->disabledModules = new class implements ModuleAvailability {
            public function isEnabled(string $code): bool { return false; }
        };
    }

    public function testDisabledCmsDoesNotLoadMenuRepository(): void
    {
        $extension = new MenuExtension(
            $this->uninitialized(MenuItemRepository::class),
            $this->createStub(UrlGeneratorInterface::class),
            new RequestStack(),
            $this->uninitialized(LocalizedPageUrlGenerator::class),
            $this->createStub(EntityManagerInterface::class),
            $this->disabledModules,
        );

        self::assertSame([], $extension->menu(1));
    }

    public function testDisabledLanguageUsesBuiltInCatalogueWithoutDatabase(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getRepository');
        $requests = new RequestStack();
        $request = Request::create('/');
        $request->setLocale('en');
        $requests->push($request);
        $translator = new SystemTranslator($entityManager, $requests, $this->disabledModules);
        $extension = new LanguageExtension($entityManager, $requests, $translator, $this->disabledModules);

        self::assertSame([], $extension->languages());
        self::assertNull($extension->current());
        self::assertNull($extension->defaultLanguage());
        self::assertSame('Dashboard', $extension->translate('nav.dashboard'));
    }

    public function testDisabledSettingsUseStaticDefaultsWithoutRepository(): void
    {
        $extension = new SettingsExtension($this->uninitialized(SettingsProvider::class), $this->disabledModules);

        self::assertSame('Shopro 4.0', $extension->setting('site_name'));
        self::assertSame('fallback', $extension->setting('unknown', 'fallback'));
        self::assertSame('22-07-2026', $extension->formatDate(new \DateTimeImmutable('2026-07-22 12:00:00 Europe/Warsaw')));
    }

    public function testDisabledMediaDoesNotReadFilesystemOrSettings(): void
    {
        $extension = new ResponsiveImageExtension('Z:/path/that/does/not/exist', $this->uninitialized(SettingsProvider::class), $this->disabledModules);

        self::assertSame('', (string) $extension->picture('/uploads/example.jpg'));
    }

    /** @template T of object @param class-string<T> $class @return T */
    private function uninitialized(string $class): object
    {
        return (new \ReflectionClass($class))->newInstanceWithoutConstructor();
    }
}
