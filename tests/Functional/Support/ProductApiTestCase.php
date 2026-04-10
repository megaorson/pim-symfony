<?php
declare(strict_types=1);

namespace App\Tests\Functional\Support;

use App\Tests\Functional\Support\Trait\AttributeTestFactoryTrait;
use App\Tests\Functional\Support\Trait\ProductTestFactoryTrait;
use App\Tests\Functional\Support\Trait\ProductTestTrait;

abstract class ProductApiTestCase extends ApiTestCase
{
    use AttributeTestFactoryTrait;
    use ProductTestFactoryTrait;
    use ProductTestTrait;
}
