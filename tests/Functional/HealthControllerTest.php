<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Module\Application\ModuleRegistry;
use App\Module\Domain\Entity\InstalledModule;
use App\Module\Infrastructure\Persistence\Doctrine\InstalledModuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class HealthControllerTest extends WebTestCase
{
    public function testHealthEndpointReportsApplicationStatus(): void
    {
        $client = self::createClient();
        $client->request('GET', '/health');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonStringEqualsJsonString(
            '{"application":"shopro4","status":"ok"}',
            (string) $client->getResponse()->getContent(),
        );
    }

    public function testReadinessEndpointReportsModuleIntegrity(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($entityManager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);
        $connection = $entityManager->getConnection();
        $connection->executeStatement('DROP TABLE IF EXISTS messenger_messages');
        $connection->executeStatement('CREATE TABLE messenger_messages (queue_name VARCHAR(190) NOT NULL)');

        $definitions = [];
        foreach (self::getContainer()->get(ModuleRegistry::class)->all() as $definition) {
            $definitions[$definition->code()] = [
                'version' => $definition->version(),
                'enabledByDefault' => $definition->required(),
            ];
        }
        $states = self::getContainer()->get(InstalledModuleRepository::class)->synchronizeAll($definitions);
        self::assertArrayHasKey('newsletter', $states);
        self::assertFalse($states['newsletter']->isEnabled());

        $client->request('GET', '/health/ready');
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"application":"shopro4","status":"ready","modules":{"status":"ok","invalid":[],"orphaned":0},"queue":{"status":"healthy","worker":"missing","pending":0,"failed":0}}',
            (string) $client->getResponse()->getContent(),
        );

        $queueCommand = new CommandTester((new Application(self::$kernel))->find('app:queue:verify'));
        self::assertSame(0, $queueCommand->execute([]));
        self::assertStringContainsString('The message queue is ready', $queueCommand->getDisplay());

        $connection->executeStatement("INSERT INTO messenger_messages (queue_name) VALUES ('async')");
        $client->request('GET', '/health/ready');
        self::assertResponseStatusCodeSame(503);
        $queuePayload = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('error', $queuePayload['queue']['status']);
        self::assertSame(1, $queuePayload['queue']['pending']);
        $queueCommand = new CommandTester((new Application(self::$kernel))->find('app:queue:verify'));
        self::assertSame(1, $queueCommand->execute([]));
        self::assertStringContainsString('blocks release readiness', $queueCommand->getDisplay());
        $connection->executeStatement("DELETE FROM messenger_messages WHERE queue_name = 'async'");

        $cms = $entityManager->find(InstalledModule::class, 'cms');
        self::assertNotNull($cms);
        try {
            $cms->synchronize('3.9.0');
            $entityManager->flush();

            $client->request('GET', '/health/ready');
            self::assertResponseStatusCodeSame(503);
            $payload = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
            self::assertSame('unavailable', $payload['status']);
            self::assertSame('error', $payload['modules']['status']);
            self::assertContains('cms', $payload['modules']['invalid']);
        } finally {
            // KernelBrowser reboots the kernel for the request, so restore through
            // the current EntityManager instead of the detached pre-request one.
            $currentEntityManager = self::getContainer()->get(EntityManagerInterface::class);
            $currentCms = $currentEntityManager->find(InstalledModule::class, 'cms');
            self::assertNotNull($currentCms);
            $currentCms->synchronize('4.0.0');
            $currentEntityManager->flush();
        }
    }
}
