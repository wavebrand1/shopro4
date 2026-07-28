<?php

declare(strict_types=1);

namespace App\Tests\Unit\Installer;

use App\Installer\Application\InstallationManager;
use PHPUnit\Framework\TestCase;

final class InstallationManagerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/shopro-installer-'.bin2hex(random_bytes(6));
        mkdir($this->directory.'/var', 0775, true);
        mkdir($this->directory.'/public/uploads', 0775, true);
        mkdir($this->directory.'/vendor', 0775, true);
        file_put_contents($this->directory.'/vendor/autoload.php', '<?php');
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->directory);
    }

    public function testItWritesProductionEnvironmentWithoutRemovingCustomValues(): void
    {
        file_put_contents($this->directory.'/.env.local', "CUSTOM_VALUE=\"kept\"\nAPP_SECRET=\"old\"\nDATABASE_URL=\"old\"\n");
        $manager = new InstallationManager($this->directory);

        $manager->writeEnvironment([
            'host' => '127.0.0.1',
            'port' => 3306,
            'name' => 'shopro',
            'user' => 'shopro user',
            'password' => 's@fe:password',
            'server_version' => '10.6.0-MariaDB',
        ], 'https://example.test');

        $environment = (string) file_get_contents($this->directory.'/.env.local');
        self::assertStringContainsString('CUSTOM_VALUE="kept"', $environment);
        self::assertStringContainsString('APP_ENV="prod"', $environment);
        self::assertStringContainsString('APP_DEBUG="0"', $environment);
        self::assertStringContainsString('DEFAULT_URI="https://example.test"', $environment);
        self::assertStringContainsString('shopro%20user:s%40fe%3Apassword@127.0.0.1:3306/shopro', $environment);
        self::assertStringNotContainsString('APP_SECRET="old"', $environment);
        self::assertSame(1, substr_count($environment, 'DATABASE_URL='));
        self::assertFalse($manager->isInstalled(), 'Pending installation must remain accessible after writing .env.local.');
        self::assertSame('https://example.test', $manager->pendingSiteUrl());
        self::assertFalse($manager->isDatabaseBootstrapped());

        $manager->markDatabaseBootstrapped();

        self::assertTrue($manager->isDatabaseBootstrapped());
        self::assertSame('https://example.test', $manager->pendingSiteUrl());
    }

    public function testLockDisablesInstaller(): void
    {
        $manager = new InstallationManager($this->directory);
        self::assertFalse($manager->isInstalled());

        $manager->lock(['site_url' => 'https://example.test']);

        self::assertTrue($manager->isInstalled());
        self::assertStringContainsString('https://example.test', (string) file_get_contents($this->directory.'/var/install.lock'));
        self::assertFileDoesNotExist($this->directory.'/var/install.pending');
    }

    public function testExistingEnvironmentIsTreatedAsInstalledWithoutLegacyLock(): void
    {
        file_put_contents($this->directory.'/.env.local', "DATABASE_URL=\"mysql://existing\"\n");

        self::assertTrue((new InstallationManager($this->directory))->isInstalled());
    }
}
