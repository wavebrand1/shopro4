<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260720200000 extends AbstractMigration
{
 public function getDescription():string{return 'Create managed legacy URL redirect map';}
 public function up(Schema $schema):void{$this->addSql('CREATE TABLE cms_url_redirect (id INT AUTO_INCREMENT NOT NULL, source_path VARCHAR(500) NOT NULL, target_path VARCHAR(500) NOT NULL, permanent TINYINT(1) DEFAULT 1 NOT NULL, active TINYINT(1) DEFAULT 1 NOT NULL, hits INT DEFAULT 0 NOT NULL, last_used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_REDIRECT_SOURCE (source_path), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');}
 public function down(Schema $schema):void{$this->addSql('DROP TABLE cms_url_redirect');}
}
