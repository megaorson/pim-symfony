<?php
declare(strict_types=1);

namespace App\Tests\Functional\Support;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

final class DatabaseCleaner
{
    private const EXCLUDED_TABLES = [
        'doctrine_migration_versions',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function clean(): void
    {
        $this->entityManager->clear();

        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $schemaManager = method_exists($connection, 'createSchemaManager')
            ? $connection->createSchemaManager()
            : $connection->getSchemaManager();

        $tables = array_filter(
            $schemaManager->listTableNames(),
            static fn (string $tableName): bool => !in_array($tableName, self::EXCLUDED_TABLES, true),
        );

        if ($tables === []) {
            return;
        }

        $this->disableForeignKeyChecks($connection);

        try {
            foreach ($tables as $tableName) {
                $connection->executeStatement($platform->getTruncateTableSQL($tableName, true));
            }
        } finally {
            $this->enableForeignKeyChecks($connection);
        }
    }

    private function disableForeignKeyChecks(Connection $connection): void
    {
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
    }

    private function enableForeignKeyChecks(Connection $connection): void
    {
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }
}
