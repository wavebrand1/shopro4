<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260717140000 extends AbstractMigration
{
    public function getDescription(): string { return 'Adds newsletter audience selection to campaigns'; }
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE newsletter_campaign ADD include_subscribers TINYINT(1) DEFAULT 1 NOT NULL, ADD selected_user_ids JSON DEFAULT NULL COMMENT '(DC2Type:json)', ADD custom_emails JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("UPDATE newsletter_campaign SET selected_user_ids='[]', custom_emails='[]'");
        $this->addSql("ALTER TABLE newsletter_campaign MODIFY selected_user_ids JSON NOT NULL COMMENT '(DC2Type:json)', MODIFY custom_emails JSON NOT NULL COMMENT '(DC2Type:json)'");
    }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE newsletter_campaign DROP include_subscribers, DROP selected_user_ids, DROP custom_emails'); }
}
