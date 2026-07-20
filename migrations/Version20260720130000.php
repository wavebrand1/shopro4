<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720130000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add one-time administrator password reset tokens.'; }
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE admin_password_reset_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', used_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_RESET_TOKEN_HASH (token_hash), INDEX IDX_RESET_TOKEN_USER (user_id), INDEX idx_password_reset_hash (token_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE admin_password_reset_token ADD CONSTRAINT FK_RESET_TOKEN_USER FOREIGN KEY (user_id) REFERENCES admin_user (id) ON DELETE CASCADE');
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE admin_password_reset_token'); }
}
