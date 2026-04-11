<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add read-optimized indexes for product collection query pipeline';
    }

    public function up(Schema $schema): void
    {
        // product
        $this->addSql('CREATE INDEX idx_product_sku ON product (sku)');
        $this->addSql('CREATE INDEX idx_product_created_at ON product (created_at)');
        $this->addSql('CREATE INDEX idx_product_updated_at ON product (updated_at)');

        // decimal
        $this->addSql('CREATE INDEX idx_pav_decimal_attr_value_product ON product_attribute_value_decimal (attribute_id, value, product_id)');
        $this->addSql('CREATE INDEX idx_pav_decimal_attr_product_value ON product_attribute_value_decimal (attribute_id, product_id, value)');

        // int
        $this->addSql('CREATE INDEX idx_pav_int_attr_value_product ON product_attribute_value_int (attribute_id, value, product_id)');
        $this->addSql('CREATE INDEX idx_pav_int_attr_product_value ON product_attribute_value_int (attribute_id, product_id, value)');

        // text
        $this->addSql('CREATE INDEX idx_pav_text_attr_value_product ON product_attribute_value_text (attribute_id, value(100), product_id)');
        $this->addSql('CREATE INDEX idx_pav_text_attr_product_value ON product_attribute_value_text (attribute_id, product_id, value(100))');

        // image
        $this->addSql('CREATE INDEX idx_pav_image_attr_product ON product_attribute_value_image (attribute_id, product_id)');
    }

    public function down(Schema $schema): void
    {
        // product
        $this->addSql('DROP INDEX idx_product_sku ON product');
        $this->addSql('DROP INDEX idx_product_created_at ON product');
        $this->addSql('DROP INDEX idx_product_updated_at ON product');

        // decimal
        $this->addSql('DROP INDEX idx_pav_decimal_attr_value_product ON product_attribute_value_decimal');
        $this->addSql('DROP INDEX idx_pav_decimal_attr_product_value ON product_attribute_value_decimal');

        // int
        $this->addSql('DROP INDEX idx_pav_int_attr_value_product ON product_attribute_value_int');
        $this->addSql('DROP INDEX idx_pav_int_attr_product_value ON product_attribute_value_int');

        // text
        $this->addSql('DROP INDEX idx_pav_text_attr_value_product ON product_attribute_value_text');
        $this->addSql('DROP INDEX idx_pav_text_attr_product_value ON product_attribute_value_text');

        // image
        $this->addSql('DROP INDEX idx_pav_image_attr_product ON product_attribute_value_image');
    }
}
