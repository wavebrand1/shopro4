<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716170000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add controlled page editor mode'; }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE cms_page ADD editor_mode VARCHAR(20) DEFAULT 'rich_text' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cms_page DROP editor_mode');
    }
}
