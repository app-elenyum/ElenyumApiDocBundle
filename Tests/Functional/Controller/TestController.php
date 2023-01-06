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

use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/test", host="api-test.example.com")
 */
class TestController
{
    /**
     * @OA\Response(
     *     response="200",
     *     description="Test"
     * )
     * @Route("/test/", methods={"GET"})
     */
    public function testAction()
    {
    }
}
