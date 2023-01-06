<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Tests\Functional;

use Elenyum\ApiDocBundle\Exception\UndocumentedArrayItemsException;
use Symfony\Component\HttpKernel\KernelInterface;

class ArrayItemsErrorTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        static::createClient([], ['HTTP_HOST' => 'api.example.com']);
    }

    public function testModelPictureDocumentation()
    {
        $this->expectException(UndocumentedArrayItemsException::class);
        $this->expectExceptionMessage('Property "Elenyum\ApiDocBundle\Tests\Functional\Entity\ArrayItemsError\Bar::things[]" is an array, but its items type isn\'t specified.');

        $this->getOpenApiDefinition();
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        return new TestKernel(TestKernel::ERROR_ARRAY_ITEMS);
    }
}
