<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Tests\Functional\Entity\NestedGroup;

use JMS\Serializer\Annotation as Serializer;

/**
 * User.
 *
 * @Serializer\ExclusionPolicy("all")
 */
class JMSPicture
{
    /**
     * @Serializer\Type("integer")
     * @Serializer\Expose
     */
    private $id;

    /**
     * @Serializer\Type("integer")
     * @Serializer\Expose
     * @Serializer\Groups({"mini"})
     */
    private $onlyDirectPictureMini;
}
