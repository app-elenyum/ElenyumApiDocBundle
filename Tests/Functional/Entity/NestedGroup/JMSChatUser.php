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
class JMSChatUser
{
    /**
     * @Serializer\Type("integer")
     * @Serializer\Expose
     */
    private $id;

    /**
     * @Serializer\Type("Elenyum\ApiDocBundle\Tests\Functional\Entity\NestedGroup\JMSPicture")
     * @Serializer\Groups({"mini"})
     * @Serializer\Expose
     */
    private $picture;

    /**
     * @Serializer\Type("array<Elenyum\ApiDocBundle\Tests\Functional\Entity\NestedGroup\JMSPicture>")
     * @Serializer\Expose
     */
    private $allPictures;
}
