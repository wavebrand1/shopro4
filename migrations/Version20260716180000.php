<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716180000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add login and profile fields to administrator accounts.'; }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE admin_user ADD username VARCHAR(80) DEFAULT NULL, ADD first_name VARCHAR(80) DEFAULT NULL, ADD last_name VARCHAR(80) DEFAULT NULL, ADD active TINYINT(1) DEFAULT 1 NOT NULL, ADD newsletter TINYINT(1) DEFAULT 0 NOT NULL");
        $this->addSql("UPDATE admin_user SET username = CASE WHEN id = 1 THEN 'admin' ELSE CONCAT('user', id) END");
        $this->addSql('ALTER TABLE admin_user MODIFY username VARCHAR(80) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ADMIN_USER_USERNAME ON admin_user (username)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_ADMIN_USER_USERNAME ON admin_user');
        $this->addSql('ALTER TABLE admin_user DROP username, DROP first_name, DROP last_name, DROP active, DROP newsletter');
    }
}
