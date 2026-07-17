<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260717150000 extends AbstractMigration
{
 public function getDescription():string{return 'Add language manager and translations';}
 public function up(Schema $s):void{
  $this->addSql("CREATE TABLE language (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, code VARCHAR(2) NOT NULL, direction VARCHAR(3) NOT NULL, author VARCHAR(120) DEFAULT NULL, subdomain VARCHAR(255) DEFAULT NULL, active TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX uniq_language_code (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
  $this->addSql("CREATE TABLE language_translation (id INT AUTO_INCREMENT NOT NULL, language_id INT NOT NULL, translation_key VARCHAR(190) NOT NULL, value LONGTEXT NOT NULL, INDEX IDX_LANGUAGE_TRANSLATION_LANGUAGE (language_id), UNIQUE INDEX uniq_language_translation_key (language_id, translation_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
  $this->addSql('ALTER TABLE language_translation ADD CONSTRAINT FK_LANGUAGE_TRANSLATION_LANGUAGE FOREIGN KEY (language_id) REFERENCES language (id) ON DELETE CASCADE');
  $this->addSql("INSERT INTO language (name,code,direction,author,subdomain,active) VALUES ('Polski','pl','ltr','WaveBrand',NULL,1)");
 }
 public function down(Schema $s):void{$this->addSql('DROP TABLE language_translation');$this->addSql('DROP TABLE language');}
}
