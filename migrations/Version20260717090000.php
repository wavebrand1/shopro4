<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717090000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add singleton system configuration compatible with Shopro Legacy settings.'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE system_settings (id INT NOT NULL, configuration JSON NOT NULL, smtp_password VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE system_settings'); }
}
