<?php
declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:generate:load-products-dbal',
    description: 'Generate large load test dataset with DBAL bulk inserts'
)]
final class GenerateLoadTestDataDbalCommand extends Command
{
    private const TEXT_COUNT = 33;
    private const INT_COUNT = 33;
    private const DECIMAL_COUNT = 33;

    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('products', InputArgument::OPTIONAL, 'How many products to generate', 1000)
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Products per batch', 500)
            ->addOption('prefix', null, InputOption::VALUE_REQUIRED, 'Prefix for generated sku/attributes', 'loadtest')
            ->addOption('recreate-attributes', null, InputOption::VALUE_NONE, 'Delete generated attributes and recreate them')
            ->addOption('truncate-products', null, InputOption::VALUE_NONE, 'Delete all products and values before generation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $productsToGenerate = max(1, (int) $input->getArgument('products'));
        $batchSize = max(1, (int) $input->getOption('batch-size'));
        $prefix = (string) $input->getOption('prefix');
        $recreateAttributes = (bool) $input->getOption('recreate-attributes');
        $truncateProducts = (bool) $input->getOption('truncate-products');

        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        if ($truncateProducts) {
            $this->truncateProducts();
        }

        $attributeMap = $this->ensureAttributesExist($prefix, $recreateAttributes);

        $progressBar = new ProgressBar($output, $productsToGenerate);
        $progressBar->start();

        $generated = 0;

        while ($generated < $productsToGenerate) {
            $currentBatchSize = min($batchSize, $productsToGenerate - $generated);
            $startIndex = $generated + 1;

            $this->connection->beginTransaction();

            try {
                $beforeMaxId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id), 0) FROM product');

                $this->insertProductsBatch($startIndex, $currentBatchSize, $prefix);

                $productIds = range($beforeMaxId + 1, $beforeMaxId + $currentBatchSize);

                $this->insertTextValuesBatch($productIds, $attributeMap['text'], $startIndex);
                $this->insertIntValuesBatch($productIds, $attributeMap['int'], $startIndex);
                $this->insertDecimalValuesBatch($productIds, $attributeMap['decimal'], $startIndex);

                $this->connection->commit();
            } catch (\Throwable $e) {
                $this->connection->rollBack();
                throw $e;
            }

            $generated += $currentBatchSize;
            $progressBar->advance($currentBatchSize);
        }

        $progressBar->finish();
        $output->writeln('');
        $output->writeln(sprintf(
            '<info>Done. Generated %d products, %d attributes per product.</info>',
            $productsToGenerate,
            self::TEXT_COUNT + self::INT_COUNT + self::DECIMAL_COUNT
        ));

        return Command::SUCCESS;
    }

    private function truncateProducts(): void
    {
        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $this->connection->executeStatement('TRUNCATE TABLE product_attribute_value_decimal');
        $this->connection->executeStatement('TRUNCATE TABLE product_attribute_value_int');
        $this->connection->executeStatement('TRUNCATE TABLE product_attribute_value_text');
        $this->connection->executeStatement('TRUNCATE TABLE product');
        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function deleteGeneratedAttributes(string $prefix): void
    {
        $sql = 'DELETE FROM product_attribute WHERE code LIKE ?';
        $this->connection->executeStatement($sql, [$prefix . '\_%']);
    }

    private function ensureAttributesExist(string $prefix, bool $recreate): array
    {
        if ($recreate) {
            $this->deleteGeneratedAttributes($prefix);
        }

        $result = [
            'text' => [],
            'int' => [],
            'decimal' => [],
        ];

        $definitions = [
            'text' => self::TEXT_COUNT,
            'int' => self::INT_COUNT,
            'decimal' => self::DECIMAL_COUNT,
        ];

        foreach ($definitions as $type => $count) {
            for ($i = 1; $i <= $count; ++$i) {
                $code = sprintf('%s_%s_%02d', $prefix, $type, $i);

                $row = $this->connection->fetchAssociative(
                    'SELECT id, code FROM product_attribute WHERE code = ? LIMIT 1',
                    [$code]
                );

                if ($row === false) {
                    $this->connection->insert('product_attribute', [
                        'code' => $code,
                        'name' => ucfirst($type) . ' ' . $i,
                        'type' => $type,
                        'is_filterable' => 1,
                        'is_sortable' => 1,
                        'is_selectable' => 1,
                    ]);

                    $attributeId = (int) $this->connection->lastInsertId();
                } else {
                    $attributeId = (int) $row['id'];
                }

                $result[$type][] = [
                    'id' => $attributeId,
                    'code' => $code,
                ];
            }
        }

        return $result;
    }

    private function insertProductsBatch(int $startIndex, int $batchSize, string $prefix): void
    {
        $placeholders = [];
        $params = [];

        for ($i = 0; $i < $batchSize; ++$i) {
            $productNumber = $startIndex + $i;
            $sku = sprintf('%s-sku-%08d', $prefix, $productNumber);

            $placeholders[] = '(?, NOW(), NOW())';
            $params[] = $sku;
        }

        $sql = sprintf(
            'INSERT INTO product (sku, created_at, updated_at) VALUES %s',
            implode(', ', $placeholders)
        );

        $this->connection->executeStatement($sql, $params);
    }

    private function insertTextValuesBatch(array $productIds, array $attributes, int $startIndex): void
    {
        $placeholders = [];
        $params = [];

        foreach ($productIds as $offset => $productId) {
            $productNumber = $startIndex + $offset;

            foreach ($attributes as $attributeIndex => $attributeMeta) {
                $placeholders[] = '(?, ?, ?, NOW(), NOW())';
                $params[] = $productId;
                $params[] = $attributeMeta['id'];
                $params[] = sprintf(
                    'Text value product=%d attr=%s idx=%d',
                    $productNumber,
                    $attributeMeta['code'],
                    $attributeIndex + 1
                );
            }
        }

        $sql = sprintf(
            'INSERT INTO product_attribute_value_text (product_id, attribute_id, value, created_at, updated_at) VALUES %s',
            implode(', ', $placeholders)
        );

        $this->connection->executeStatement($sql, $params);
    }

    private function insertIntValuesBatch(array $productIds, array $attributes, int $startIndex): void
    {
        $placeholders = [];
        $params = [];

        foreach ($productIds as $offset => $productId) {
            $productNumber = $startIndex + $offset;

            foreach ($attributes as $attributeIndex => $attributeMeta) {
                $placeholders[] = '(?, ?, ?, NOW(), NOW())';
                $params[] = $productId;
                $params[] = $attributeMeta['id'];
                $params[] = ($productNumber * 100) + $attributeIndex + 1;
            }
        }

        $sql = sprintf(
            'INSERT INTO product_attribute_value_int (product_id, attribute_id, value, created_at, updated_at) VALUES %s',
            implode(', ', $placeholders)
        );

        $this->connection->executeStatement($sql, $params);
    }

    private function insertDecimalValuesBatch(array $productIds, array $attributes, int $startIndex): void
    {
        $placeholders = [];
        $params = [];

        foreach ($productIds as $offset => $productId) {
            $productNumber = $startIndex + $offset;

            foreach ($attributes as $attributeIndex => $attributeMeta) {
                $placeholders[] = '(?, ?, ?, NOW(), NOW())';
                $params[] = $productId;
                $params[] = $attributeMeta['id'];
                $params[] = number_format($productNumber + (($attributeIndex + 1) / 100), 2, '.', '');
            }
        }

        $sql = sprintf(
            'INSERT INTO product_attribute_value_decimal (product_id, attribute_id, value, created_at, updated_at) VALUES %s',
            implode(', ', $placeholders)
        );

        $this->connection->executeStatement($sql, $params);
    }
}
