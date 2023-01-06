<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Tests\Functional\Controller;

use Symfony\Component\Routing\Annotation\Route;

if (\PHP_VERSION_ID >= 80100) {
    /**
     * @Route("/api", name="api_", host="api.example.com")
     */
    class ApiController extends ApiController81
    {
    }
} else {
    /**
     * @Route("/api", name="api_", host="api.example.com")
     */
    class ApiController extends ApiController80
    {
    }
}
