<?php

declare(strict_types=1);

namespace App\Tests\Unit\Migration;

require_once dirname(__DIR__, 3).'/migrations/Version20260728090000.php';

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use DoctrineMigrations\Version20260728090000;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class NewsletterAudienceMigrationTest extends TestCase
{
    public function testUpgradeDoesNotAddColumnsThatAlreadyExist(): void
    {
        $schema = $this->schema(includeNewColumns: true);
        $migration = new Version20260728090000(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            new NullLogger(),
        );

        $migration->up($schema);
        $sql = array_map(static fn ($query): string => $query->getStatement(), $migration->getSql());

        self::assertCount(3, $sql);
        self::assertStringNotContainsString(' ADD newsletter ', implode("\n", $sql));
        self::assertStringNotContainsString(' ADD selected_site_user_ids ', implode("\n", $sql));
        self::assertStringContainsString('WHERE selected_site_user_ids IS NULL', $sql[0]);
    }

    public function testUpgradeAddsColumnsOnAnOlderSchema(): void
    {
        $schema = $this->schema(includeNewColumns: false);
        $migration = new Version20260728090000(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            new NullLogger(),
        );

        $migration->up($schema);
        $sql = array_map(static fn ($query): string => $query->getStatement(), $migration->getSql());
        $statements = implode("\n", $sql);

        self::assertCount(5, $sql);
        self::assertStringContainsString('ALTER TABLE site_user ADD newsletter', $statements);
        self::assertStringContainsString('ALTER TABLE newsletter_campaign ADD selected_site_user_ids', $statements);
    }

    private function schema(bool $includeNewColumns): Schema
    {
        $schema = new Schema();
        $siteUsers = $schema->createTable('site_user');
        $siteUsers->addColumn('id', 'integer');
        $campaigns = $schema->createTable('newsletter_campaign');
        $campaigns->addColumn('id', 'integer');

        if ($includeNewColumns) {
            $siteUsers->addColumn('newsletter', 'boolean');
            $campaigns->addColumn('selected_site_user_ids', 'json');
        }

        return $schema;
    }
}
