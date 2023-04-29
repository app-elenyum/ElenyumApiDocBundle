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

use ApiPlatform\Core\Annotation\ApiProperty;
use Elenyum\ApiDocBundle\Tests\Functional\EntityExcluded;

// BC Api-Platform < 3.x
if (!class_exists(ApiProperty::class)) {
    class Dummy extends EntityExcluded\ApiPlatform3\Dummy
    {
    }
} else {
    class_alias(EntityExcluded\ApiPlatform2\Dummy::class, Dummy::class);
}
