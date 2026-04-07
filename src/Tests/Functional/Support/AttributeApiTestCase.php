<?php

namespace App\Tests\Functional\Support;

use App\Tests\Functional\Support\Trait\AttributeTest;
use App\Tests\Functional\Support\Trait\AttributeTestFactoryTrait;

abstract class AttributeApiTestCase extends ApiTestCase
{
    use AttributeTest;
    use AttributeTestFactoryTrait;
}
