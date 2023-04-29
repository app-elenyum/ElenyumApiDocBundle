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

/**
 * @Route(host="api.example.com")
 */
class UndocumentedController
{
    /**
     * This path is excluded by the config (only /api allowed).
     *
     * @Route("/undocumented", methods={"GET"})
     */
    public function undocumentedAction()
    {
    }
}
