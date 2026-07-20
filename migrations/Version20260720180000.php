<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720180000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add secure website account email activation tokens.'; }
    public function up(Schema $schema): void { $this->addSql('ALTER TABLE site_user ADD activation_token_hash VARCHAR(64) DEFAULT NULL, ADD activation_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\''); $this->addSql('CREATE UNIQUE INDEX UNIQ_SITE_USER_ACTIVATION_TOKEN ON site_user (activation_token_hash)'); }
    public function down(Schema $schema): void { $this->addSql('DROP INDEX UNIQ_SITE_USER_ACTIVATION_TOKEN ON site_user'); $this->addSql('ALTER TABLE site_user DROP activation_token_hash, DROP activation_expires_at'); }
}
