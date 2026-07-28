<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Cms\Domain\Entity\Page;
use App\Identity\Domain\Entity\AdminUser;
use App\Language\Domain\Entity\Language;
use App\Mail\Application\SystemEmailTemplateCatalog;
use App\Mail\Domain\Entity\EmailTemplate;
use App\Settings\Domain\Entity\SystemSettings;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ReleaseReadinessCommandTest extends KernelTestCase
{
    public function testStrictVerificationAcceptsCompleteReleaseData(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($entityManager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $administrator = new AdminUser('admin@example.test', 'admin');
        $administrator->setPassword('test-password-hash');
        $entityManager->persist($administrator);

        $language = new Language();
        $language->setName('Polski');
        $language->setCode('pl');
        $language->setActive(true);
        $language->setDefaultLanguage(true);
        $entityManager->persist($language);

        $homepage = new Page();
        $homepage->setTitle('Strona główna');
        $homepage->setSlug('strona-glowna');
        $homepage->setPublished(true);
        $homepage->setHomePage(true);
        $entityManager->persist($homepage);

        $errorPage = new Page();
        $errorPage->setTitle('Nie znaleziono');
        $errorPage->setSlug('404');
        $errorPage->setPublished(true);
        $errorPage->setErrorPage(true);
        $entityManager->persist($errorPage);

        $settings = new SystemSettings();
        $settings->setConfiguration([
            'site_url' => 'https://shopro.example.test',
            'site_email' => 'admin@example.test',
            'mail_from_address' => 'noreply@example.test',
            'smtp_host' => 'smtp.example.test',
        ]);
        $entityManager->persist($settings);

        $catalog = self::getContainer()->get(SystemEmailTemplateCatalog::class);
        foreach ($catalog->codes() as $code) {
            $template = new EmailTemplate();
            $template->setCode($code);
            $template->setName($code);
            $template->setSubject($code);
            $template->setContent('<p>Test</p>');
            $template->setSystem(true);
            $entityManager->persist($template);
        }
        $entityManager->flush();

        $application = new Application(self::$kernel);
        $command = $application->find('app:release:verify');
        $tester = new CommandTester($command);

        self::assertSame(0, $tester->execute(['--strict' => true]));
        self::assertStringContainsString('Release data is ready', $tester->getDisplay());
    }

    public function testVerificationRejectsEmptyInstallation(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($entityManager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find('app:release:verify'));

        self::assertSame(1, $tester->execute([]));
        self::assertStringContainsString('No active panel administrator exists', $tester->getDisplay());
        self::assertStringContainsString('No published homepage is assigned', $tester->getDisplay());
        self::assertStringContainsString('Missing system email templates', $tester->getDisplay());
    }
}
