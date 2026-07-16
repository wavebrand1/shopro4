<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create hierarchical CMS menu compatible with the legacy menu contract.';
    }

    public function up(Schema $schema): void
    {
        $menu = $schema->createTable('cms_menu_item');
        $menu->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $menu->addColumn('parent_id', Types::INTEGER, ['notnull' => false]);
        $menu->addColumn('page_id', Types::INTEGER, ['notnull' => false]);
        $menu->addColumn('name', Types::STRING, ['length' => 120]);
        $menu->addColumn('caption', Types::STRING, ['length' => 200, 'notnull' => false]);
        $menu->addColumn('content_type', Types::STRING, ['length' => 20]);
        $menu->addColumn('link', Types::STRING, ['length' => 500, 'notnull' => false]);
        $menu->addColumn('target', Types::STRING, ['length' => 10, 'default' => '_self']);
        $menu->addColumn('position', Types::INTEGER, ['default' => 0]);
        $menu->addColumn('home_page', Types::BOOLEAN, ['default' => false]);
        $menu->addColumn('place', Types::INTEGER, ['default' => 1]);
        $menu->addColumn('active', Types::BOOLEAN, ['default' => true]);
        $menu->setPrimaryKey(['id']);
        $menu->addIndex(['parent_id'], 'IDX_MENU_PARENT');
        $menu->addIndex(['page_id'], 'IDX_MENU_PAGE');
        $menu->addForeignKeyConstraint('cms_menu_item', ['parent_id'], ['id'], ['onDelete' => 'SET NULL'], 'FK_MENU_PARENT');
        $menu->addForeignKeyConstraint('cms_page', ['page_id'], ['id'], ['onDelete' => 'SET NULL'], 'FK_MENU_PAGE');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('cms_menu_item');
    }
}
