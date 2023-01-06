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

use JMS\Serializer\Annotation as Serializer;
use Elenyum\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class JMSDualComplex
{
    /**
     * @Serializer\Type("integer")
     */
    private $id;

    /**
     * @OA\Property(ref=@Model(type=JMSComplex::class))
     */
    private $complex;

    /**
     * @OA\Property(ref=@Model(type=JMSUser::class))
     */
    private $user;
}
