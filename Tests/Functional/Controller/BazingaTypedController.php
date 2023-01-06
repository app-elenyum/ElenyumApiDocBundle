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

use Elenyum\ApiDocBundle\Annotation\Model;
use Elenyum\ApiDocBundle\Tests\Functional\EntityExcluded\BazingaUserTyped;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(host="api.example.com")
 */
class BazingaTypedController
{
    /**
     * @Route("/api/bazinga_typed", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @Model(type=BazingaUserTyped::class)
     * )
     */
    public function userTypedAction()
    {
    }
}
