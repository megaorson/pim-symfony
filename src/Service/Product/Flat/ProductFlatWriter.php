<?php
declare(strict_types=1);

namespace App\Service\Product\Flat;

use Doctrine\DBAL\Connection;

final readonly class ProductFlatWriter
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function upsertRows(string $tableName, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        foreach ($rows as $row) {
            $columns = array_keys($row);

            $insertColumns = implode(', ', $columns);
            $insertPlaceholders = implode(', ', array_map(
                static fn (string $column): string => ':' . $column,
                $columns
            ));

            $updateAssignments = implode(', ', array_map(
                static fn (string $column): string => sprintf('%s = VALUES(%s)', $column, $column),
                array_values(array_filter(
                    $columns,
                    static fn (string $column): bool => $column !== 'product_id'
                ))
            ));

            $sql = sprintf(
                'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
                $tableName,
                $insertColumns,
                $insertPlaceholders,
                $updateAssignments,
            );

            $this->connection->executeStatement($sql, $row);
        }
    }
}
