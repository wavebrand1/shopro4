<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260716150000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add legacy CMS page settings'; }
    public function up(Schema $schema): void { $this->addSql("ALTER TABLE cms_page ADD caption VARCHAR(600) DEFAULT '' NOT NULL, ADD seo_title VARCHAR(200) DEFAULT '' NOT NULL, ADD description LONGTEXT DEFAULT '' NOT NULL, ADD keywords LONGTEXT DEFAULT '' NOT NULL, ADD meta LONGTEXT DEFAULT '' NOT NULL, ADD javascript LONGTEXT DEFAULT '' NOT NULL, ADD canonical VARCHAR(300) DEFAULT '' NOT NULL, ADD access VARCHAR(20) DEFAULT 'Public' NOT NULL, ADD follow TINYINT(1) DEFAULT 1 NOT NULL, ADD home_page TINYINT(1) DEFAULT 0 NOT NULL, ADD error_page TINYINT(1) DEFAULT 0 NOT NULL, ADD admin_only TINYINT(1) DEFAULT 0 NOT NULL, ADD login_page TINYINT(1) DEFAULT 0 NOT NULL, ADD activation_page TINYINT(1) DEFAULT 0 NOT NULL, ADD account_page TINYINT(1) DEFAULT 0 NOT NULL, ADD registration_page TINYINT(1) DEFAULT 0 NOT NULL, ADD search_page TINYINT(1) DEFAULT 0 NOT NULL, ADD sitemap_page TINYINT(1) DEFAULT 0 NOT NULL, ADD profile_page TINYINT(1) DEFAULT 0 NOT NULL, ADD terms_page TINYINT(1) DEFAULT 0 NOT NULL"); }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE cms_page DROP caption, DROP seo_title, DROP description, DROP keywords, DROP meta, DROP javascript, DROP canonical, DROP access, DROP follow, DROP home_page, DROP error_page, DROP admin_only, DROP login_page, DROP activation_page, DROP account_page, DROP registration_page, DROP search_page, DROP sitemap_page, DROP profile_page, DROP terms_page'); }
}
