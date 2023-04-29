<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Tests\Functional\Entity;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(ref="#/components/schemas/Test")
 */
class EntityWithRef
{
    /**
     * @var string
     */
    public $ignored = 'this property should be ignored because of the annotation above';
}
