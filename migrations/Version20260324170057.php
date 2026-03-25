<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260324170057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, sku VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE product_attribute (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(100) NOT NULL, type VARCHAR(50) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE product_attribute_value_decimal (id INT AUTO_INCREMENT NOT NULL, value DOUBLE PRECISION NOT NULL, attribute_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_5DF81CECB6E62EFA (attribute_id), INDEX IDX_5DF81CEC4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE product_attribute_value_image (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(255) NOT NULL, attribute_id INT NOT NULL, product_id INT DEFAULT NULL, INDEX IDX_73E8F88DB6E62EFA (attribute_id), INDEX IDX_73E8F88D4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE product_attribute_value_int (id INT AUTO_INCREMENT NOT NULL, value INT NOT NULL, attribute_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_A3C4CB49B6E62EFA (attribute_id), INDEX IDX_A3C4CB494584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE product_attribute_value_text (id INT AUTO_INCREMENT NOT NULL, value LONGTEXT DEFAULT NULL, attribute_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_885A48F8B6E62EFA (attribute_id), INDEX IDX_885A48F84584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('ALTER TABLE product_attribute_value_decimal ADD CONSTRAINT FK_5DF81CECB6E62EFA FOREIGN KEY (attribute_id) REFERENCES product_attribute (id)');
        $this->addSql('ALTER TABLE product_attribute_value_decimal ADD CONSTRAINT FK_5DF81CEC4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_attribute_value_image ADD CONSTRAINT FK_73E8F88DB6E62EFA FOREIGN KEY (attribute_id) REFERENCES product_attribute (id)');
        $this->addSql('ALTER TABLE product_attribute_value_image ADD CONSTRAINT FK_73E8F88D4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_attribute_value_int ADD CONSTRAINT FK_A3C4CB49B6E62EFA FOREIGN KEY (attribute_id) REFERENCES product_attribute (id)');
        $this->addSql('ALTER TABLE product_attribute_value_int ADD CONSTRAINT FK_A3C4CB494584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_attribute_value_text ADD CONSTRAINT FK_885A48F8B6E62EFA FOREIGN KEY (attribute_id) REFERENCES product_attribute (id)');
        $this->addSql('ALTER TABLE product_attribute_value_text ADD CONSTRAINT FK_885A48F84584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_attribute_value_decimal DROP FOREIGN KEY FK_5DF81CECB6E62EFA');
        $this->addSql('ALTER TABLE product_attribute_value_decimal DROP FOREIGN KEY FK_5DF81CEC4584665A');
        $this->addSql('ALTER TABLE product_attribute_value_image DROP FOREIGN KEY FK_73E8F88DB6E62EFA');
        $this->addSql('ALTER TABLE product_attribute_value_image DROP FOREIGN KEY FK_73E8F88D4584665A');
        $this->addSql('ALTER TABLE product_attribute_value_int DROP FOREIGN KEY FK_A3C4CB49B6E62EFA');
        $this->addSql('ALTER TABLE product_attribute_value_int DROP FOREIGN KEY FK_A3C4CB494584665A');
        $this->addSql('ALTER TABLE product_attribute_value_text DROP FOREIGN KEY FK_885A48F8B6E62EFA');
        $this->addSql('ALTER TABLE product_attribute_value_text DROP FOREIGN KEY FK_885A48F84584665A');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_attribute');
        $this->addSql('DROP TABLE product_attribute_value_decimal');
        $this->addSql('DROP TABLE product_attribute_value_image');
        $this->addSql('DROP TABLE product_attribute_value_int');
        $this->addSql('DROP TABLE product_attribute_value_text');
    }
}
