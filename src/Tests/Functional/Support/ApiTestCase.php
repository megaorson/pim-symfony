<?php
declare(strict_types=1);

namespace App\Tests\Functional\Support;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase as BaseApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Support\Trait\ApiAssertionTrait;
use App\Tests\Functional\Support\Trait\AttributeTest;
use App\Tests\Functional\Support\Trait\AttributeTestFactoryTrait;
use App\Tests\Functional\Support\Trait\JsonRequestHelperTrait;
use App\Tests\Functional\Support\Trait\ProductTestFactoryTrait;
use Doctrine\ORM\EntityManagerInterface;

abstract class ApiTestCase extends BaseApiTestCase
{
    use JsonRequestHelperTrait;
    use ApiAssertionTrait;
    use AttributeTest;
    use AttributeTestFactoryTrait;
    use ProductTestFactoryTrait;

    protected Client $client;
    protected EntityManagerInterface $entityManager;
    protected DatabaseCleaner $databaseCleaner;

    protected static ?bool $alwaysBootKernel = true;

    protected function setUp(): void
    {
        parent::setUp();

        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->databaseCleaner = $container->get(DatabaseCleaner::class);

        $this->databaseCleaner->clean();
    }

    protected function tearDown(): void
    {
        $this->entityManager->clear();

        unset(
            $this->client,
            $this->entityManager,
            $this->databaseCleaner,
        );

        parent::tearDown();
    }

    protected function flushAndClear(): void
    {
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    protected function persistAndFlush(object ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();
    }
}
