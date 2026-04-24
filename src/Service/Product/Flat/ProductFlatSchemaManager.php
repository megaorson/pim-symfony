<?php
declare(strict_types=1);

namespace App\Service\Product\Flat;

use Doctrine\DBAL\Connection;

final readonly class ProductFlatSchemaManager
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @param array{
     *   columns: array<string, string>,
     *   indexes: list<array{type: string, name: string, columns: list<string>}>
     * } $structure
     */
    public function rebuildTable(string $tableName, array $structure): void
    {
        $this->connection->executeStatement(sprintf('DROP TABLE IF EXISTS %s', $tableName));

        $columnSql = [];
        foreach ($structure['columns'] as $columnName => $definition) {
            $columnSql[] = sprintf('%s %s', $columnName, $definition);
        }

        $indexSql = [];
        foreach ($structure['indexes'] as $index) {
            $columns = implode(', ', $index['columns']);

            $indexSql[] = match ($index['type']) {
                'primary' => sprintf('PRIMARY KEY (%s)', $columns),
                'unique' => sprintf('UNIQUE KEY %s (%s)', $index['name'], $columns),
                'index' => sprintf('KEY %s (%s)', $index['name'], $columns),
                default => throw new \RuntimeException(sprintf('Unsupported index type "%s".', $index['type'])),
            };
        }

        $sql = sprintf(
            'CREATE TABLE %s (%s) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB',
            $tableName,
            implode(', ', array_merge($columnSql, $indexSql)),
        );

        $this->connection->executeStatement($sql);
    }
}
