<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260717160000 extends AbstractMigration
{
 public function getDescription():string{return 'Add locale and regional settings to languages';}
 public function up(Schema $s):void{
  $this->addSql("ALTER TABLE language ADD default_language TINYINT(1) DEFAULT 0 NOT NULL, ADD locale VARCHAR(20) DEFAULT 'pl_PL' NOT NULL, ADD currency VARCHAR(3) DEFAULT 'PLN' NOT NULL, ADD currency_symbol VARCHAR(10) DEFAULT 'zł' NOT NULL, ADD decimal_separator VARCHAR(5) DEFAULT ',' NOT NULL, ADD thousands_separator VARCHAR(5) DEFAULT ' ' NOT NULL");
  $this->addSql("UPDATE language SET default_language=1 WHERE code='pl'");
 }
 public function down(Schema $s):void{$this->addSql('ALTER TABLE language DROP default_language, DROP locale, DROP currency, DROP currency_symbol, DROP decimal_separator, DROP thousands_separator');}
}
