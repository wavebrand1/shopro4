<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720100000 extends AbstractMigration
{
    public function getDescription(): string { return 'Adds the administrative audit log.'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, action VARCHAR(120) NOT NULL, message VARCHAR(255) NOT NULL, username VARCHAR(180) DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, data JSON NOT NULL, important TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_audit_created (created_at), INDEX idx_audit_type (type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE audit_log'); }
}
