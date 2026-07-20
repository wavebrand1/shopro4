<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720110000 extends AbstractMigration
{
    public function getDescription(): string { return 'Preserve administrator access before enabling assigned panel roles.'; }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE admin_user SET roles = '[\"ROLE_ADMIN\"]' WHERE roles = '[]' OR roles IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE admin_user SET roles = '[]' WHERE roles = '[\"ROLE_ADMIN\"]'");
    }
}
