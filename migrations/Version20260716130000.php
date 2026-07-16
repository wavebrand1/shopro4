<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create administrator accounts and CMS pages.';
    }

    public function up(Schema $schema): void
    {
        $users = $schema->createTable('admin_user');
        $users->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $users->addColumn('email', Types::STRING, ['length' => 180]);
        $users->addColumn('roles', Types::JSON);
        $users->addColumn('password', Types::STRING, ['length' => 255]);
        $users->setPrimaryKey(['id']);
        $users->addUniqueIndex(['email'], 'UNIQ_AD8A54A9E7927C74');

        $pages = $schema->createTable('cms_page');
        $pages->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $pages->addColumn('title', Types::STRING, ['length' => 200]);
        $pages->addColumn('slug', Types::STRING, ['length' => 180]);
        $pages->addColumn('content', Types::TEXT);
        $pages->addColumn('published', Types::BOOLEAN, ['default' => false]);
        $pages->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $pages->addColumn('updated_at', Types::DATETIME_IMMUTABLE);
        $pages->setPrimaryKey(['id']);
        $pages->addUniqueIndex(['slug'], 'UNIQ_D39C1B5D989D9B62');

        $messages = $schema->createTable('messenger_messages');
        $messages->addColumn('id', Types::BIGINT, ['autoincrement' => true]);
        $messages->addColumn('body', Types::TEXT);
        $messages->addColumn('headers', Types::TEXT);
        $messages->addColumn('queue_name', Types::STRING, ['length' => 190]);
        $messages->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $messages->addColumn('available_at', Types::DATETIME_IMMUTABLE);
        $messages->addColumn('delivered_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $messages->setPrimaryKey(['id']);
        $messages->addIndex(
            ['queue_name', 'available_at', 'delivered_at', 'id'],
            'IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750',
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('messenger_messages');
        $schema->dropTable('cms_page');
        $schema->dropTable('admin_user');
    }
}
