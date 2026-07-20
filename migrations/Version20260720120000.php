<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720120000 extends AbstractMigration
{
    public function getDescription(): string { return 'Track administrator account creation and last successful login.'; }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE admin_user ADD created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD last_login_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('UPDATE admin_user SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL');
        $this->addSql("ALTER TABLE admin_user MODIFY created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin_user DROP created_at, DROP last_login_at');
    }
}
