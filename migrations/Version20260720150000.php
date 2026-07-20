<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720150000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add membership groups used by front users and restricted pages.'; }
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE membership (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE membership'); }
}
