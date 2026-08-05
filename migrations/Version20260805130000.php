<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805130000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add module content references to menu items'; }
    public function up(Schema $schema): void { $this->addSql('ALTER TABLE cms_menu_item ADD module_reference VARCHAR(120) DEFAULT NULL'); }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE cms_menu_item DROP module_reference'); }
}
