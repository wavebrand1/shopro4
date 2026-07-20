<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720160000 extends AbstractMigration
{
    public function getDescription(): string { return 'Allow pages to be assigned to multiple membership groups.'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cms_page_membership (page_id INT NOT NULL, membership_id INT NOT NULL, INDEX IDX_PAGE_MEMBERSHIP_PAGE (page_id), INDEX IDX_PAGE_MEMBERSHIP_MEMBERSHIP (membership_id), PRIMARY KEY(page_id, membership_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cms_page_membership ADD CONSTRAINT FK_PAGE_MEMBERSHIP_PAGE FOREIGN KEY (page_id) REFERENCES cms_page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cms_page_membership ADD CONSTRAINT FK_PAGE_MEMBERSHIP_MEMBERSHIP FOREIGN KEY (membership_id) REFERENCES membership (id) ON DELETE RESTRICT');
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE cms_page_membership'); }
}
