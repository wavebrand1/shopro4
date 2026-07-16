<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260716160000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add GrapesJS project and generated CSS to CMS pages'; }
    public function up(Schema $schema): void { $this->addSql("ALTER TABLE cms_page ADD builder_data LONGTEXT DEFAULT '' NOT NULL, ADD builder_css LONGTEXT DEFAULT '' NOT NULL"); }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE cms_page DROP builder_data, DROP builder_css'); }
}
