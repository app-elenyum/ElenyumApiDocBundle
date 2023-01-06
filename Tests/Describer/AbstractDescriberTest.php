<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Tests\Describer;

use Elenyum\ApiDocBundle\Describer\DescriberInterface;
use OpenApi\Annotations\OpenApi;
use PHPUnit\Framework\TestCase;

abstract class AbstractDescriberTest extends TestCase
{
    /** @var DescriberInterface */
    protected $describer;

    protected function getOpenApiDoc(): OpenApi
    {
        $api = new OpenApi([]);
        $this->describer->describe($api);

        return $api;
    }
}
