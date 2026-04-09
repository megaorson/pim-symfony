<?php
declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\ApiResource\Dto\ProductCollectionOutput;
use App\ApiResource\Dto\ProductInput;
use App\ApiResource\Dto\ProductOutput;
use App\ApiResource\Dto\ProductPatchInput;
use App\Controller\Api\ProductImageUploadAction;
use App\State\ProductCollectionProvider;
use App\State\ProductCreateProcessor;
use App\State\ProductDeleteProcessor;
use App\State\ProductItemProvider;
use App\State\ProductUpdateProcessor;
use ApiPlatform\OpenApi\Model\RequestBody;

#[ApiResource(
    shortName: 'Product',
    operations: [
        new GetCollection(
            uriTemplate: '/products',
            provider: ProductCollectionProvider::class,
            output: ProductCollectionOutput::class,
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'page',
                        in: 'query',
                        schema: ['type' => 'integer', 'default' => 1]
                    ),
                    new Parameter(
                        name: 'limit',
                        in: 'query',
                        schema: ['type' => 'integer', 'default' => 10]
                    )
                ]
            )
        ),
        new Get(
            uriTemplate: '/products/{id}',
            provider: ProductItemProvider::class,
            output: ProductOutput::class
        ),
        new Post(
            uriTemplate: '/products',
            input: ProductInput::class,
            processor: ProductCreateProcessor::class,
            output: ProductOutput::class
        ),
        new Patch(
            uriTemplate: '/products/{id}',
            input: ProductPatchInput::class,
            processor: ProductUpdateProcessor::class,
            output: ProductOutput::class,
            read: false
        ),
        new Delete(
            uriTemplate: '/products/{id}',
            processor: ProductDeleteProcessor::class,
            read: false
        ),
        new Post(
            uriTemplate: '/products/{id}/images',
            controller: ProductImageUploadAction::class,
            deserialize: false,
            read: false,
            name: 'product_image_upload',
            openapi: new Operation(
                summary: 'Upload image for product image attribute',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'attributeCode' => [
                                        'type' => 'string',
                                        'example' => 'main_image',
                                    ],
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                ],
                                'required' => ['attributeCode', 'file'],
                            ],
                        ],
                    ])
                )
            )
        ),

    ]
)]
final class ProductApiResource
{
}
