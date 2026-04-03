<?php
declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Dto\ProductAttributeInput;
use App\ApiResource\Dto\ProductAttributeOutput;
use App\ApiResource\Dto\ProductAttributePatchInput;
use App\State\ProductAttributeCollectionProvider;
use App\State\ProductAttributeCreateProcessor;
use App\State\ProductAttributeDeleteProcessor;
use App\State\ProductAttributeItemProvider;
use App\State\ProductAttributeUpdateProcessor;

#[ApiResource(
    shortName: 'Product Attribute',
    description: 'Product Attribute orerations',
    operations: [
        new GetCollection(
            uriTemplate: '/attributes',
            provider: ProductAttributeCollectionProvider::class,
            output: ProductAttributeOutput::class
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
