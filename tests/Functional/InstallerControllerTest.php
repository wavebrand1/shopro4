<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InstallerControllerTest extends WebTestCase
{
    public function testUninstalledHomepageRedirectsToInstaller(): void
    {
        $state = $this->prepareInstallationState();

        try {
            $client = self::createClient();
            $client->request('GET', '/');

            self::assertResponseRedirects('/install');
        } finally {
            $this->restoreInstallationState($state);
        }
    }

    public function testRequirementsPageIsAvailableBeforeInstallation(): void
    {
        $state = $this->prepareInstallationState();

        try {
            $client = self::createClient();
            $client->request('GET', '/install');

            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('h1', 'Sprawdzenie serwera');
            self::assertSelectorExists('meta[name="robots"][content="noindex,nofollow"]');
            self::assertSelectorExists('a[href="/install/database"]');
        } finally {
            $this->restoreInstallationState($state);
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

    /** @return array{lock:?string,pending:?string} */
    private function prepareInstallationState(): array
    {
        $directory = dirname(__DIR__, 2).'/var';
        $lock = $directory.'/install.lock';
        $pending = $directory.'/install.pending';
        $state = [
            'lock' => is_file($lock) ? (string) file_get_contents($lock) : null,
            'pending' => is_file($pending) ? (string) file_get_contents($pending) : null,
        ];
        if (!is_dir($directory)) mkdir($directory, 0775, true);
        if (is_file($lock)) unlink($lock);
        file_put_contents($pending, json_encode([
            'site_url' => 'https://example.test',
            'database_bootstrapped' => false,
        ], JSON_THROW_ON_ERROR));

        return $state;
    }

    /** @param array{lock:?string,pending:?string} $state */
    private function restoreInstallationState(array $state): void
    {
        $directory = dirname(__DIR__, 2).'/var';
        foreach (['lock', 'pending'] as $name) {
            $path = $directory.'/install.'.$name;
            if ($state[$name] !== null) file_put_contents($path, $state[$name]);
            elseif (is_file($path)) unlink($path);
        }
    }
}
