<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260322194722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_attribute_value_decimal (id INT AUTO_INCREMENT NOT NULL, value DOUBLE PRECISION NOT NULL, sku_id INT NOT NULL, attribute_id INT NOT NULL, INDEX IDX_5DF81CEC1777D41C (sku_id), INDEX IDX_5DF81CECB6E62EFA (attribute_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE product_attribute_value_image (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(255) NOT NULL, sku_id INT NOT NULL, attribute_id INT NOT NULL, INDEX IDX_73E8F88D1777D41C (sku_id), INDEX IDX_73E8F88DB6E62EFA (attribute_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE product_attribute_value_int (id INT AUTO_INCREMENT NOT NULL, value INT NOT NULL, sku_id INT NOT NULL, attribute_id INT NOT NULL, INDEX IDX_A3C4CB491777D41C (sku_id), INDEX IDX_A3C4CB49B6E62EFA (attribute_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE product_attribute_value_text (id INT AUTO_INCREMENT NOT NULL, value LONGTEXT DEFAULT NULL, sku_id INT NOT NULL, attribute_id INT NOT NULL, INDEX IDX_885A48F81777D41C (sku_id), INDEX IDX_885A48F8B6E62EFA (attribute_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('ALTER TABLE product_attribute_value_decimal ADD CONSTRAINT FK_5DF81CEC1777D41C FOREIGN KEY (sku_id) REFERENCES sku (id)');
        $this->addSql('ALTER TABLE product_attribute_value_decimal ADD CONSTRAINT FK_5DF81CECB6E62EFA FOREIGN KEY (attribute_id) REFERENCES product_attribute (id)');
        $this->addSql('ALTER TABLE product_attribute_value_image ADD CONSTRAINT FK_73E8F88D1777D41C FOREIGN KEY (sku_id) REFERENCES sku (id)');
        $this->addSql('ALTER TABLE product_attribute_value_image ADD CONSTRAINT FK_73E8F88DB6E62EFA FOREIGN KEY (attribute_id) REFERENCES product_attribute (id)');
        $this->addSql('ALTER TABLE product_attribute_value_int ADD CONSTRAINT FK_A3C4CB491777D41C FOREIGN KEY (sku_id) REFERENCES sku (id)');
        $this->addSql('ALTER TABLE product_attribute_value_int ADD CONSTRAINT FK_A3C4CB49B6E62EFA FOREIGN KEY (attribute_id) REFERENCES product_attribute (id)');
        $this->addSql('ALTER TABLE product_attribute_value_text ADD CONSTRAINT FK_885A48F81777D41C FOREIGN KEY (sku_id) REFERENCES sku (id)');
        $this->addSql('ALTER TABLE product_attribute_value_text ADD CONSTRAINT FK_885A48F8B6E62EFA FOREIGN KEY (attribute_id) REFERENCES product_attribute (id)');
        $this->addSql('ALTER TABLE product_attribute_value DROP FOREIGN KEY `FK_CCC4BE1F1777D41C`');
        $this->addSql('ALTER TABLE product_attribute_value DROP FOREIGN KEY `FK_CCC4BE1FB6E62EFA`');
        $this->addSql('DROP TABLE product_attribute_value');
        $this->addSql('ALTER TABLE product_attribute CHANGE ccode code VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE sku CHANGE price price NUMERIC(10, 2) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_attribute_value (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_general_ci`, attribute_id INT NOT NULL, sku_id INT NOT NULL, INDEX IDX_CCC4BE1FB6E62EFA (attribute_id), INDEX IDX_CCC4BE1F1777D41C (sku_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE product_attribute_value ADD CONSTRAINT `FK_CCC4BE1F1777D41C` FOREIGN KEY (sku_id) REFERENCES sku (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE product_attribute_value ADD CONSTRAINT `FK_CCC4BE1FB6E62EFA` FOREIGN KEY (attribute_id) REFERENCES product_attribute (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE product_attribute_value_decimal DROP FOREIGN KEY FK_5DF81CEC1777D41C');
        $this->addSql('ALTER TABLE product_attribute_value_decimal DROP FOREIGN KEY FK_5DF81CECB6E62EFA');
        $this->addSql('ALTER TABLE product_attribute_value_image DROP FOREIGN KEY FK_73E8F88D1777D41C');
        $this->addSql('ALTER TABLE product_attribute_value_image DROP FOREIGN KEY FK_73E8F88DB6E62EFA');
        $this->addSql('ALTER TABLE product_attribute_value_int DROP FOREIGN KEY FK_A3C4CB491777D41C');
        $this->addSql('ALTER TABLE product_attribute_value_int DROP FOREIGN KEY FK_A3C4CB49B6E62EFA');
        $this->addSql('ALTER TABLE product_attribute_value_text DROP FOREIGN KEY FK_885A48F81777D41C');
        $this->addSql('ALTER TABLE product_attribute_value_text DROP FOREIGN KEY FK_885A48F8B6E62EFA');
        $this->addSql('DROP TABLE product_attribute_value_decimal');
        $this->addSql('DROP TABLE product_attribute_value_image');
        $this->addSql('DROP TABLE product_attribute_value_int');
        $this->addSql('DROP TABLE product_attribute_value_text');
        $this->addSql('ALTER TABLE product_attribute CHANGE code ccode VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE sku CHANGE price price DOUBLE PRECISION NOT NULL');
    }
}
