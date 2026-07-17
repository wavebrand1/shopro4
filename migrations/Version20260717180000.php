<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260717180000 extends AbstractMigration
{
 public function getDescription():string{return 'Remove redundant translations for the default language';}
 public function up(Schema $schema):void{$this->addSql('DELETE pt FROM cms_page_translation pt INNER JOIN language l ON l.id=pt.language_id WHERE l.default_language=1');}
 public function down(Schema $schema):void{}
}
