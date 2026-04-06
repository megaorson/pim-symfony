<?php
declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Dto\ProductAttributeCollectionOutput;
use App\ApiResource\Dto\ProductAttributeInput;
use App\ApiResource\Dto\ProductAttributeOutput;
use App\ApiResource\Dto\ProductAttributePatchInput;
use App\State\ProductAttributeCollectionProvider;
use App\State\ProductAttributeCreateProcessor;
use App\State\ProductAttributeDeleteProcessor;
use App\State\ProductAttributeItemProvider;
use App\State\ProductAttributeUpdateProcessor;
use ApiPlatform\OpenApi\Model\Parameter;

#[ApiResource(
    shortName: 'Product Attribute',
    description: 'Product Attribute orerations',
    operations: [
        new GetCollection(
            uriTemplate: '/attributes',
            provider: ProductAttributeCollectionProvider::class,
            output: ProductAttributeCollectionOutput::class,
            openapi: new \ApiPlatform\OpenApi\Model\Operation(
                parameters: [
                    new Parameter(
                        name: 'page',
                        in: 'query',
                        description: 'Page number, starts from 1',
                        required: false,
                        schema: ['type' => 'integer', 'default' => 1, 'minimum' => 1],
                        example: 1,
                    ),
                    new Parameter(
                        name: 'limit',
                        in: 'query',
                        description: 'Number of items per page',
                        required: false,
                        schema: ['type' => 'integer', 'default' => 10, 'minimum' => 1],
                        example: 10,
                    ),
                ],
            ),
        ),
        new Get(
            uriTemplate: '/attributes/{id}',
            provider: ProductAttributeItemProvider::class,
            output: ProductAttributeOutput::class
        ),
        new Post(
            uriTemplate: '/attributes',
            input: ProductAttributeInput::class,
            processor: ProductAttributeCreateProcessor::class,
            output: ProductAttributeOutput::class
        ),
        new Patch(
            uriTemplate: '/attributes/{id}',
            input: ProductAttributePatchInput::class,
            processor: ProductAttributeUpdateProcessor::class,
            output: ProductAttributeOutput::class,
            read: false
        ),
        new Delete(
            uriTemplate: '/attributes/{id}',
            processor: ProductAttributeDeleteProcessor::class,
            read: false
        ),
    ]
)]
final class ProductAttributeApiResource
{
}
