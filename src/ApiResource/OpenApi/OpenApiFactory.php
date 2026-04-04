<?php
declare(strict_types=1);

namespace App\ApiResource\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\OpenApi;
use App\Service\Eav\FilterDocumentationBuilder;
use App\Service\Eav\SelectDocumentationBuilder;
use App\Service\Eav\SortDocumentationBuilder;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
        private readonly FilterDocumentationBuilder $filterDocumentationBuilder,
        private readonly SortDocumentationBuilder $sortDocumentationBuilder,
        private readonly SelectDocumentationBuilder $selectDocumentationBuilder,
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

            $parameters = $this->upsertParameter(
                $parameters,
                new Parameter(
                    name: 'filter',
                    in: 'query',
                    description: $this->filterDocumentationBuilder->buildFilterDescription(),
                    schema: ['type' => 'string'],
                    example: $this->filterDocumentationBuilder->buildFilterExample(),
                )
            );

            $parameters = $this->upsertParameter(
                $parameters,
                new Parameter(
                    name: 'select',
                    in: 'query',
                    description: $this->selectDocumentationBuilder->buildSelectDescription(),
                    schema: ['type' => 'string'],
                    example: $this->selectDocumentationBuilder->buildSelectExample(),
                )
            );

            $parameters = $this->upsertParameter(
                $parameters,
                new Parameter(
                    name: 'sort',
                    in: 'query',
                    description: $this->sortDocumentationBuilder->buildSortDescription(),
                    schema: ['type' => 'string'],
                    example: $this->sortDocumentationBuilder->buildSortExample(),
                )
            );

            $updatedGet = $get->withParameters($parameters);

            $paths->addPath($path, $pathItem->withGet($updatedGet));
        }

        return $openApi;
    }

    /**
     * @param array<int, Parameter> $parameters
     * @return array<int, Parameter>
     */
    private function upsertParameter(array $parameters, Parameter $parameter): array
    {
        $result = [];

        foreach ($parameters as $existingParameter) {
            if (
                $existingParameter->getName() === $parameter->getName()
                && $existingParameter->getIn() === $parameter->getIn()
            ) {
                continue;
            }

            $result[] = $existingParameter;
        }

        $result[] = $parameter;

        return $result;
    }
}
