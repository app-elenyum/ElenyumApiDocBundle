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

use Symfony\Component\Serializer\Annotation\DiscriminatorMap;

/**
 * @DiscriminatorMap(typeProperty="type", mapping={
 *      "one": SymfonyDiscriminatorOne::class,
 *      "two": SymfonyDiscriminatorTwo::class,
 * })
 */
abstract class SymfonyDiscriminator
{
    /**
     * @var string
     */
    public $type;
}
