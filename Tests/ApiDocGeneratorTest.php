<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Tests;

use Elenyum\ApiDocBundle\ApiDocGenerator;
use Elenyum\ApiDocBundle\Describer\DefaultDescriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ApiDocGeneratorTest extends TestCase
{
    public function testCache()
    {
        $adapter = new ArrayAdapter();
        $generator = new ApiDocGenerator([new DefaultDescriber()], [], $adapter);

        $this->assertEquals(json_encode($generator->generate()), json_encode($adapter->getItem('openapi_doc')->get()));
    }

    public function testCacheWithCustomId()
    {
        $adapter = new ArrayAdapter();
        $generator = new ApiDocGenerator([new DefaultDescriber()], [], $adapter, 'custom_id');

        $this->assertEquals(json_encode($generator->generate()), json_encode($adapter->getItem('custom_id')->get()));
    }
}
