<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720230000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add recoverable trash for CMS pages'; }
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE cms_page ADD deleted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('CREATE INDEX IDX_PAGE_DELETED_AT ON cms_page (deleted_at)');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_PAGE_DELETED_AT ON cms_page');
        $this->addSql('ALTER TABLE cms_page DROP deleted_at');
    }
}
