<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720220000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add scheduled CMS page publication window'; }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE cms_page ADD publish_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD unpublish_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('CREATE INDEX IDX_PAGE_PUBLICATION_START ON cms_page (published, publish_at)');
        $this->addSql('CREATE INDEX IDX_PAGE_PUBLICATION_END ON cms_page (published, unpublish_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_PAGE_PUBLICATION_START ON cms_page');
        $this->addSql('DROP INDEX IDX_PAGE_PUBLICATION_END ON cms_page');
        $this->addSql('ALTER TABLE cms_page DROP publish_at, DROP unpublish_at');
    }
}
