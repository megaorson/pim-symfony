<?php
declare(strict_types=1);

namespace App\Tests\Functional\Support;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase as BaseApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Service\Eav\AttributeMetadataProvider;
use App\Service\Product\Flat\ProductFlatReindexService;
use App\Tests\Functional\Support\Trait\ApiAssertionTrait;
use App\Tests\Functional\Support\Trait\JsonRequestHelperTrait;
use Doctrine\ORM\EntityManagerInterface;

abstract class ApiTestCase extends BaseApiTestCase
{
    use JsonRequestHelperTrait;
    use ApiAssertionTrait;

    protected Client $client;
    protected EntityManagerInterface $entityManager;
    protected DatabaseCleaner $databaseCleaner;

    protected ProductFlatReindexService $productFlatReindexService;

    protected AttributeMetadataProvider $attributeMetadataProvider;

    protected static ?bool $alwaysBootKernel = true;

    protected function setUp(): void
    {
        parent::setUp();

        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->databaseCleaner = $container->get(DatabaseCleaner::class);
        $this->productFlatReindexService = $container->get(ProductFlatReindexService::class);
        $this->attributeMetadataProvider = $container->get(AttributeMetadataProvider::class);

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

    protected function rebuildFlatIndex(): void
    {
        $this->attributeMetadataProvider->clearCache();
        $this->productFlatReindexService->rebuild();
    }
}
