<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260321202352 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE product_attribute (id INT AUTO_INCREMENT NOT NULL, ccode VARCHAR(100) NOT NULL, type VARCHAR(50) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE product_attribute_value (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(255) DEFAULT NULL, attribute_id INT NOT NULL, sku_id INT NOT NULL, INDEX IDX_CCC4BE1FB6E62EFA (attribute_id), INDEX IDX_CCC4BE1F1777D41C (sku_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE sku (id INT AUTO_INCREMENT NOT NULL, sku VARCHAR(100) NOT NULL, price DOUBLE PRECISION NOT NULL, product_id INT NOT NULL, INDEX IDX_F9038C44584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('ALTER TABLE product_attribute_value ADD CONSTRAINT FK_CCC4BE1FB6E62EFA FOREIGN KEY (attribute_id) REFERENCES product_attribute (id)');
        $this->addSql('ALTER TABLE product_attribute_value ADD CONSTRAINT FK_CCC4BE1F1777D41C FOREIGN KEY (sku_id) REFERENCES sku (id)');
        $this->addSql('ALTER TABLE sku ADD CONSTRAINT FK_F9038C44584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_attribute_value DROP FOREIGN KEY FK_CCC4BE1FB6E62EFA');
        $this->addSql('ALTER TABLE product_attribute_value DROP FOREIGN KEY FK_CCC4BE1F1777D41C');
        $this->addSql('ALTER TABLE sku DROP FOREIGN KEY FK_F9038C44584665A');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_attribute');
        $this->addSql('DROP TABLE product_attribute_value');
        $this->addSql('DROP TABLE sku');
    }
}
