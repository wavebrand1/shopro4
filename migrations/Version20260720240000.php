<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720240000 extends AbstractMigration
{
    public function getDescription(): string { return 'Prevent concurrent CMS page edits from overwriting each other'; }
    public function up(Schema $schema): void { $this->addSql('ALTER TABLE cms_page ADD lock_version INT DEFAULT 1 NOT NULL'); }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE cms_page DROP lock_version'); }
}
