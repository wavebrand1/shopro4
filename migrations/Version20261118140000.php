<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add configurable submenu column count to menu items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cms_menu_item ADD submenu_columns INT NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cms_menu_item DROP submenu_columns');
    }
}
