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

use Elenyum\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api", host="api.example.com")
 * @Security(name="basic")
 */
class ClassApiController
{
    /**
     * @Route("/security/class")
     * @OA\Response(response="201", description="")
     */
    public function securityAction()
    {
    }
}
