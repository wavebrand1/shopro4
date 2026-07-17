<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717100000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add per-user API credentials and queued newsletter campaign history.'; }
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE admin_user ADD api_enabled TINYINT(1) DEFAULT 0 NOT NULL, ADD api_token_hash VARCHAR(64) DEFAULT NULL, ADD api_token_prefix VARCHAR(12) DEFAULT NULL, ADD api_scopes JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("UPDATE admin_user SET api_scopes = '[]'");
        $this->addSql("ALTER TABLE admin_user MODIFY api_scopes JSON NOT NULL COMMENT '(DC2Type:json)'");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ADMIN_USER_API_TOKEN_HASH ON admin_user (api_token_hash)');
        $this->addSql("CREATE TABLE newsletter_campaign (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(180) NOT NULL, content LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', queued_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE newsletter_delivery (id INT AUTO_INCREMENT NOT NULL, campaign_id INT NOT NULL, recipient VARCHAR(180) NOT NULL, status VARCHAR(20) NOT NULL, error LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_NEWSLETTER_DELIVERY_CAMPAIGN (campaign_id), UNIQUE INDEX uniq_campaign_recipient (campaign_id, recipient), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE newsletter_delivery ADD CONSTRAINT FK_NEWSLETTER_DELIVERY_CAMPAIGN FOREIGN KEY (campaign_id) REFERENCES newsletter_campaign (id) ON DELETE CASCADE');
        $this->addSql("CREATE TABLE email_template (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(80) NOT NULL, name VARCHAR(160) NOT NULL, subject VARCHAR(180) NOT NULL, content LONGTEXT NOT NULL, system TINYINT(1) DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_EMAIL_TEMPLATE_CODE (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("INSERT INTO email_template (code, name, subject, content, system) VALUES ('security_alert', 'Alert bezpieczeństwa', 'Alert bezpieczeństwa konta', '<p>Wykryto zdarzenie bezpieczeństwa dla konta {{ user }}.</p>', 1), ('account_activation', 'Aktywacja konta', 'Aktywuj swoje konto', '<p>Aby aktywować konto, kliknij: <a href=\"{{ activation_url }}\">Aktywuj konto</a>.</p>', 1)");
    }
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE newsletter_delivery DROP FOREIGN KEY FK_NEWSLETTER_DELIVERY_CAMPAIGN');
        $this->addSql('DROP TABLE newsletter_delivery');
        $this->addSql('DROP TABLE newsletter_campaign');
        $this->addSql('DROP TABLE email_template');
        $this->addSql('DROP INDEX UNIQ_ADMIN_USER_API_TOKEN_HASH ON admin_user');
        $this->addSql('ALTER TABLE admin_user DROP api_enabled, DROP api_token_hash, DROP api_token_prefix, DROP api_scopes');
    }
}
