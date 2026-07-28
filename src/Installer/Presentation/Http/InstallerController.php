<?php

declare(strict_types=1);

namespace App\Installer\Presentation\Http;

use App\Identity\Domain\Entity\AdminUser;
use App\Installer\Application\InstallationManager;
use App\Settings\Application\SensitiveDataCipher;
use App\Settings\Application\SettingsProvider;
use App\Settings\Infrastructure\Persistence\Doctrine\SystemSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/install', name: 'installer_')]
final class InstallerController extends AbstractController
{
    #[Route('', name: 'requirements', methods: ['GET'])]
    public function requirements(InstallationManager $installer): Response
    {
        $this->denyInstalled($installer);

        return $this->render('installer/requirements.html.twig', [
            'checks' => $installer->requirements(),
            'can_continue' => $installer->requirementsPass(),
            'step' => 1,
        ]);
    }

    #[Route('/database', name: 'database', methods: ['GET', 'POST'])]
    public function database(Request $request, InstallationManager $installer): Response
    {
        $this->denyInstalled($installer);
        if (!$installer->requirementsPass()) return $this->redirectToRoute('installer_requirements');

        $values = [
            'host' => '127.0.0.1',
            'port' => '3306',
            'name' => '',
            'user' => '',
            'server_version' => '10.6.0-MariaDB',
            'site_url' => $request->getSchemeAndHttpHost(),
        ];
        $error = null;

        if ($request->isMethod('POST')) {
            $values = array_replace($values, array_intersect_key($request->request->all(), $values));
            if (!$this->isCsrfTokenValid('shopro-install-database', (string) $request->request->get('_token'))) {
                $error = 'Sesja formularza wygasła. Odśwież stronę i spróbuj ponownie.';
            } else {
                $database = [
                    'host' => trim((string) $values['host']),
                    'port' => max(1, min(65535, (int) $values['port'])),
                    'name' => trim((string) $values['name']),
                    'user' => trim((string) $values['user']),
                    'password' => (string) $request->request->get('password'),
                    'server_version' => trim((string) $values['server_version']),
                ];
                $siteUrl = rtrim(trim((string) $values['site_url']), '/');

                if ($database['host'] === '' || $database['name'] === '' || $database['user'] === '') {
                    $error = 'Uzupełnij host, nazwę bazy i użytkownika.';
                } elseif (filter_var($siteUrl, FILTER_VALIDATE_URL) === false || !str_starts_with($siteUrl, 'http')) {
                    $error = 'Podaj pełny adres witryny rozpoczynający się od https://.';
                } else {
                    try {
                        $installer->testDatabase($database);
                        $installer->writeEnvironment($database, $siteUrl);

                        return $this->redirectToRoute('installer_configuration');
                    } catch (\Throwable $exception) {
                        $error = 'Nie udało się połączyć z bazą lub zapisać konfiguracji: '.$exception->getMessage();
                    }
                }
            }
        }

        return $this->render('installer/database.html.twig', [
            'values' => $values,
            'error' => $error,
            'step' => 2,
        ]);
    }

    #[Route('/configuration', name: 'configuration', methods: ['GET', 'POST'])]
    public function configuration(
        Request $request,
        InstallationManager $installer,
        EntityManagerInterface $entityManager,
        SystemSettingsRepository $settingsRepository,
        SensitiveDataCipher $cipher,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $this->denyInstalled($installer);
        $siteUrl = $installer->pendingSiteUrl();
        if ($siteUrl === null) return $this->redirectToRoute('installer_database');

        $bootstrapError = null;
        if (!$installer->isDatabaseBootstrapped()) {
            try {
                $installer->bootstrapDatabase();
                $installer->markDatabaseBootstrapped();
            } catch (\Throwable $exception) {
                $bootstrapError = $exception->getMessage();
            }
        }

        $defaults = SettingsProvider::defaults();
        $values = [
            'site_name' => 'Moja strona',
            'site_url' => $siteUrl,
            'site_email' => '',
            'timezone' => 'Europe/Warsaw',
            'language' => 'pl',
            'admin_username' => 'admin',
            'admin_email' => '',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_encryption' => 'tls',
            'smtp_user' => '',
            'mail_from_address' => '',
            'mail_from_name' => '',
        ];
        $error = $bootstrapError;

        if ($request->isMethod('POST') && $bootstrapError === null) {
            $values = array_replace($values, array_intersect_key($request->request->all(), $values));
            if (!$this->isCsrfTokenValid('shopro-install-configuration', (string) $request->request->get('_token'))) {
                $error = 'Sesja formularza wygasła. Odśwież stronę i spróbuj ponownie.';
            } else {
                $password = (string) $request->request->get('admin_password');
                $confirmation = (string) $request->request->get('admin_password_confirmation');
                $error = $this->validateConfiguration($values, $password, $confirmation);
                if ($error === null) {
                    try {
                        $admin = $entityManager->getRepository(AdminUser::class)->findOneBy([
                            'username' => mb_strtolower(trim((string) $values['admin_username'])),
                        ]);
                        if (!$admin instanceof AdminUser) {
                            $admin = new AdminUser((string) $values['admin_email'], (string) $values['admin_username']);
                        } else {
                            $admin->setEmail((string) $values['admin_email']);
                        }
                        $admin->setRoles(['ROLE_ADMIN']);
                        $admin->setActive(true);
                        $admin->setPassword($passwordHasher->hashPassword($admin, $password));
                        $entityManager->persist($admin);

                        $settings = $settingsRepository->get();
                        $configuration = array_replace($defaults, [
                            'site_name' => trim((string) $values['site_name']),
                            'site_url' => rtrim(trim((string) $values['site_url']), '/'),
                            'site_email' => mb_strtolower(trim((string) $values['site_email'])),
                            'timezone' => (string) $values['timezone'],
                            'language' => (string) $values['language'],
                            'locale' => $values['language'] === 'en' ? 'en_GB' : 'pl_PL',
                            'show_language' => true,
                            'smtp_host' => trim((string) $values['smtp_host']),
                            'smtp_port' => max(1, min(65535, (int) $values['smtp_port'])),
                            'smtp_encryption' => (string) $values['smtp_encryption'],
                            'smtp_user' => trim((string) $values['smtp_user']),
                            'mail_from_address' => mb_strtolower(trim((string) $values['mail_from_address'])),
                            'mail_from_name' => trim((string) ($values['mail_from_name'] ?: $values['site_name'])),
                        ]);
                        $settings->setConfiguration($configuration);
                        $smtpPassword = (string) $request->request->get('smtp_password');
                        if ($smtpPassword !== '') $settings->setSmtpPassword($cipher->encrypt($smtpPassword));
                        $entityManager->persist($settings);
                        $entityManager->flush();

                        $final = $installer->finalize();
                        $installer->lock(['site_url' => $configuration['site_url']]);

                        return $this->render('installer/complete.html.twig', [
                            'step' => 4,
                            'site_url' => $configuration['site_url'],
                            'admin_username' => $admin->getUsername(),
                            'cron_installed' => $final['cron'],
                            'cron_output' => $final['cron_output'],
                            'cron_command' => $installer->scheduledTaskCommand(),
                        ]);
                    } catch (\Throwable $exception) {
                        $error = 'Instalacja nie została zakończona: '.$exception->getMessage();
                    }
                }
            }
        }

        return $this->render('installer/configuration.html.twig', [
            'values' => $values,
            'error' => $error,
            'step' => 3,
            'bootstrap_failed' => $bootstrapError !== null,
        ]);
    }

    /** @param array<string,mixed> $values */
    private function validateConfiguration(array $values, string $password, string $confirmation): ?string
    {
        if (trim((string) $values['site_name']) === '') return 'Podaj nazwę witryny.';
        if (filter_var($values['site_url'], FILTER_VALIDATE_URL) === false) return 'Podaj prawidłowy adres witryny.';
        if (filter_var($values['site_email'], FILTER_VALIDATE_EMAIL) === false) return 'Podaj prawidłowy e-mail witryny.';
        if (filter_var($values['admin_email'], FILTER_VALIDATE_EMAIL) === false) return 'Podaj prawidłowy e-mail administratora.';
        if (preg_match('/^[a-z0-9._-]{3,80}$/i', trim((string) $values['admin_username'])) !== 1) {
            return 'Login administratora musi mieć 3–80 znaków i może zawierać litery, cyfry, kropkę, myślnik lub podkreślenie.';
        }
        if (mb_strlen($password) < 12) return 'Hasło administratora musi mieć co najmniej 12 znaków.';
        if (!hash_equals($password, $confirmation)) return 'Hasła administratora nie są identyczne.';
        if ($values['language'] !== 'pl') return 'Pierwsza instalacja wymaga polskiego języka bazowego. Kolejne języki dodasz później w panelu.';
        if (!in_array($values['timezone'], \DateTimeZone::listIdentifiers(), true)) return 'Wybierz prawidłową strefę czasową.';
        if (!in_array($values['smtp_encryption'], ['tls', 'ssl', 'none'], true)) return 'Wybierz prawidłowy sposób szyfrowania SMTP.';
        if ($values['smtp_host'] !== '' && filter_var($values['mail_from_address'], FILTER_VALIDATE_EMAIL) === false) {
            return 'Dla skonfigurowanej poczty podaj prawidłowy adres nadawcy.';
        }

        return null;
    }

    private function denyInstalled(InstallationManager $installer): void
    {
        if ($installer->isInstalled()) throw $this->createNotFoundException();
    }
}
