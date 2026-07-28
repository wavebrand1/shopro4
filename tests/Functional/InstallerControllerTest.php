<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InstallerControllerTest extends WebTestCase
{
    public function testRequirementsPageIsAvailableBeforeInstallation(): void
    {
        $lock = dirname(__DIR__, 2).'/var/install.lock';
        $backup = is_file($lock) ? (string) file_get_contents($lock) : null;
        if (is_file($lock)) unlink($lock);

        try {
            $client = self::createClient();
            $client->request('GET', '/install');

            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('h1', 'Sprawdzenie serwera');
            self::assertSelectorExists('meta[name="robots"][content="noindex,nofollow"]');
            self::assertSelectorExists('a[href="/install/database"]');
        } finally {
            if ($backup !== null) file_put_contents($lock, $backup);
            elseif (is_file($lock)) unlink($lock);
        }
    }

    public function testInstallerReturnsNotFoundAfterLockIsCreated(): void
    {
        $lock = dirname(__DIR__, 2).'/var/install.lock';
        $backup = is_file($lock) ? (string) file_get_contents($lock) : null;
        if (!is_dir(dirname($lock))) mkdir(dirname($lock), 0775, true);
        file_put_contents($lock, '{}');

        try {
            $client = self::createClient();
            $client->request('GET', '/install');

            self::assertResponseStatusCodeSame(404);
        } finally {
            if ($backup !== null) file_put_contents($lock, $backup);
            elseif (is_file($lock)) unlink($lock);
        }
    }
}
