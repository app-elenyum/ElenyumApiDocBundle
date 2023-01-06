<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\RouteDescriber;

use OpenApi\Annotations\OpenApi;
use Symfony\Component\Routing\Route;

interface RouteDescriberInterface
{
    public function describe(OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod);
}
