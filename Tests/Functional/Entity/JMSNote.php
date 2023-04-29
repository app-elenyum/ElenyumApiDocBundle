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

/**
 * JMSNote.
 *
 * @Serializer\ExclusionPolicy("all")
 */
class JMSNote
{
    /**
     * @Serializer\Type("string")
     * @Serializer\Expose
     */
    private $long;

    /**
     * @Serializer\Type("int")
     * @Serializer\Expose
     */
    private $short;
}
