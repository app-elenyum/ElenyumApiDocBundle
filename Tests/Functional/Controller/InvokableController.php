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
 * @Route("/api/invoke", host="api.example.com", name="invokable", methods={"GET"})
 * @OA\Response(
 *    response=200,
 *    description="Invokable!"
 * )
 */
class InvokableController
{
    public function __invoke()
    {
    }
}
