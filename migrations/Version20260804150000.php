<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804150000 extends AbstractMigration
{
    public function getDescription(): string{return 'Create the global public slug registry and reserve existing CMS page URLs';}
    public function up(Schema $schema):void
    {
        $this->addSql("CREATE TABLE public_slug (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(10) DEFAULT '' NOT NULL, slug VARCHAR(180) NOT NULL, owner_type VARCHAR(80) NOT NULL, owner_id INT NOT NULL, UNIQUE INDEX UNIQ_PUBLIC_SLUG_PATH (locale, slug), UNIQUE INDEX UNIQ_PUBLIC_SLUG_OWNER (owner_type, owner_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("INSERT INTO public_slug (locale, slug, owner_type, owner_id) SELECT '', slug, 'cms_page', id FROM cms_page");
        $this->addSql("INSERT INTO public_slug (locale, slug, owner_type, owner_id) SELECT language.code, translation.slug, 'cms_page_translation', translation.id FROM cms_page_translation translation INNER JOIN language ON language.id = translation.language_id");
    }
    public function down(Schema $schema):void{$this->addSql('DROP TABLE public_slug');}
}
