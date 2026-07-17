<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;use Doctrine\Migrations\AbstractMigration;
final class Version20260717190000 extends AbstractMigration
{
 public function getDescription():string{return 'Add localized menu labels, captions and custom links';}
 public function up(Schema $schema):void{$this->addSql('CREATE TABLE cms_menu_item_translation (id INT AUTO_INCREMENT NOT NULL, menu_item_id INT NOT NULL, language_id INT NOT NULL, name VARCHAR(120) NOT NULL, caption VARCHAR(200) DEFAULT NULL, link VARCHAR(500) DEFAULT NULL, INDEX IDX_MENU_TRANSLATION_ITEM (menu_item_id), INDEX IDX_MENU_TRANSLATION_LANGUAGE (language_id), UNIQUE INDEX uniq_menu_item_language (menu_item_id, language_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');$this->addSql('ALTER TABLE cms_menu_item_translation ADD CONSTRAINT FK_MENU_TRANSLATION_ITEM FOREIGN KEY (menu_item_id) REFERENCES cms_menu_item (id) ON DELETE CASCADE');$this->addSql('ALTER TABLE cms_menu_item_translation ADD CONSTRAINT FK_MENU_TRANSLATION_LANGUAGE FOREIGN KEY (language_id) REFERENCES language (id) ON DELETE CASCADE');}
 public function down(Schema $schema):void{$this->addSql('DROP TABLE cms_menu_item_translation');}
}
