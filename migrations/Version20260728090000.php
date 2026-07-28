<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move newsletter subscriptions and campaign audience selection to website users';
    }

    public function up(Schema $schema): void
    {
        $siteUser = $schema->getTable('site_user');
        if (!$siteUser->hasColumn('newsletter')) {
            $this->addSql('ALTER TABLE site_user ADD newsletter TINYINT(1) DEFAULT 0 NOT NULL');
        }

        $campaign = $schema->getTable('newsletter_campaign');
        if (!$campaign->hasColumn('selected_site_user_ids')) {
            $this->addSql("ALTER TABLE newsletter_campaign ADD selected_site_user_ids JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
        }

        // Preserve selections if the column was already introduced manually or
        // by an earlier interrupted deployment; initialize only missing values.
        $this->addSql("UPDATE newsletter_campaign SET selected_site_user_ids='[]' WHERE selected_site_user_ids IS NULL");
        $this->addSql("ALTER TABLE newsletter_campaign MODIFY selected_site_user_ids JSON NOT NULL COMMENT '(DC2Type:json)'");
        $this->addSql(
            'UPDATE site_user site INNER JOIN admin_user admin '.
            'ON CONVERT(admin.email USING utf8mb4) COLLATE utf8mb4_unicode_ci = '.
            'CONVERT(site.email USING utf8mb4) COLLATE utf8mb4_unicode_ci '.
            'SET site.newsletter = 1 WHERE admin.newsletter = 1'
        );
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('newsletter_campaign')->hasColumn('selected_site_user_ids')) {
            $this->addSql('ALTER TABLE newsletter_campaign DROP selected_site_user_ids');
        }
        if ($schema->getTable('site_user')->hasColumn('newsletter')) {
            $this->addSql('ALTER TABLE site_user DROP newsletter');
        }
    }
}
