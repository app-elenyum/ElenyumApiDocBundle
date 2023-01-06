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
use Elenyum\ApiDocBundle\Tests\Functional\Entity\ArrayItemsError\Foo;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(host="api.example.com")
 */
class ArrayItemsErrorController
{
    /**
     * @Route("/api/error", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @Model(type=Foo::class)
     * )
     */
    public function errorAction()
    {
    }
}
