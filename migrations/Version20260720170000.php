<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720170000 extends AbstractMigration
{
    public function getDescription(): string { return 'Create separate website user accounts with membership assignments.'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE site_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(80) NOT NULL, password VARCHAR(255) NOT NULL, active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_login_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_SITE_USER_EMAIL (email), UNIQUE INDEX UNIQ_SITE_USER_USERNAME (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE site_user_membership (site_user_id INT NOT NULL, membership_id INT NOT NULL, INDEX IDX_SITE_USER_MEMBERSHIP_USER (site_user_id), INDEX IDX_SITE_USER_MEMBERSHIP_MEMBERSHIP (membership_id), PRIMARY KEY(site_user_id, membership_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE site_user_membership ADD CONSTRAINT FK_SITE_USER_MEMBERSHIP_USER FOREIGN KEY (site_user_id) REFERENCES site_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE site_user_membership ADD CONSTRAINT FK_SITE_USER_MEMBERSHIP_MEMBERSHIP FOREIGN KEY (membership_id) REFERENCES membership (id) ON DELETE RESTRICT');
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE site_user_membership'); $this->addSql('DROP TABLE site_user'); }
}
