<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423192000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create product_flat runtime state and blue/green flat tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE product_flat_runtime_state (
    id TINYINT UNSIGNED NOT NULL,
    active_table VARCHAR(32) NOT NULL,
    build_status VARCHAR(32) NOT NULL,
    build_version VARCHAR(64) DEFAULT NULL,
    updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE product_flat_a (
    product_id INT UNSIGNED NOT NULL,
    sku VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY(product_id),
    UNIQUE KEY uniq_product_flat_a_sku (sku),
    KEY idx_product_flat_a_updated_at (updated_at),
    KEY idx_product_flat_a_created_at (created_at)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE product_flat_b (
    product_id INT UNSIGNED NOT NULL,
    sku VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY(product_id),
    UNIQUE KEY uniq_product_flat_b_sku (sku),
    KEY idx_product_flat_b_updated_at (updated_at),
    KEY idx_product_flat_b_created_at (created_at)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO product_flat_runtime_state (id, active_table, build_status, build_version, updated_at)
VALUES (1, 'product_flat_a', 'ready', 'initial', NOW())
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE product_flat_b');
        $this->addSql('DROP TABLE product_flat_a');
        $this->addSql('DROP TABLE product_flat_runtime_state');
    }
}
