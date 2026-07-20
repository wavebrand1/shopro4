<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720190000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add one-time website user password reset tokens.'; }
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE site_password_reset_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', used_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_SITE_RESET_TOKEN_HASH (token_hash), INDEX IDX_SITE_RESET_TOKEN_USER (user_id), INDEX idx_site_password_reset_hash (token_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE site_password_reset_token ADD CONSTRAINT FK_SITE_RESET_TOKEN_USER FOREIGN KEY (user_id) REFERENCES site_user (id) ON DELETE CASCADE');
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE site_password_reset_token'); }
}
