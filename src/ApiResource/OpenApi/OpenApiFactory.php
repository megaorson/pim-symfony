<?php
declare(strict_types=1);

namespace App\ApiResource\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\OpenApi;
use App\Service\Eav\FilterDocumentationBuilder;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
        private readonly FilterDocumentationBuilder $documentationBuilder
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = $openApi->getPaths();

        foreach ($paths->getPaths() as $path => $pathItem) {
            if ($path !== '/api/products') {
                continue;
            }

            $get = $pathItem->getGet();

            if (!$get instanceof Operation) {
                continue;
            }

            $parameters = $get->getParameters() ?? [];

            $parameters[] = new Parameter(
                name: 'filter',
                in: 'query',
                description: $this->documentationBuilder->buildFilterDescription(),
                schema: ['type' => 'string'],
                example: $this->documentationBuilder->buildFilterExample()
            );

            $parameters[] = new Parameter(
                name: 'select',
                in: 'query',
                description: $this->documentationBuilder->buildSelectDescription(),
                schema: ['type' => 'string'],
                example: $this->documentationBuilder->buildSelectExample()
            );

            $updatedGet = $get->withParameters($parameters);

            $paths->addPath($path, $pathItem->withGet($updatedGet));
        }

        return $openApi;
    }
}
