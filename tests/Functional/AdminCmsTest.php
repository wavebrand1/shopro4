<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Identity\Domain\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminCmsTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testAdministratorCanLogInAndPublishPage(): void
    {
        $user = new AdminUser('admin@example.test');
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, 'very-secure-password'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('GET', '/admin');
        self::assertResponseRedirects('/admin/login');

        $this->client->followRedirect();
        $this->client->submitForm('Zaloguj się', [
            '_username' => 'admin@example.test',
            '_password' => 'very-secure-password',
        ]);
        self::assertResponseRedirects('/admin');

        $this->client->followRedirect();
        self::assertSelectorTextContains('h1', 'Dzień dobry');

        $this->client->request('GET', '/admin/pages/new');
        $this->client->submitForm('Zapisz', [
            'page[title]' => 'O nas',
            'page[slug]' => 'o-nas',
            'page[content]' => 'Pierwsza treść Shopro 4.0.',
            'page[published]' => true,
        ]);
        self::assertResponseRedirects('/admin/pages');

        $this->client->request('GET', '/o-nas');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'O nas');
        self::assertSelectorTextContains('.content-page__body', 'Pierwsza treść Shopro 4.0.');
    }
}
