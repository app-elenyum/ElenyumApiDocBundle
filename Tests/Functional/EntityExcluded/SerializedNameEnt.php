<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Tests\Functional\EntityExcluded;

use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @author Guilhem N. <guilhem.niot@gmail.com>
 */
class SerializedNameEnt
{
    /**
     * @SerializedName("notfoo")
     *
     * @var string
     */
    public $foo;

    /**
     * Tests serialized name feature.
     *
     * @SerializedName("notwhatyouthink")
     */
    public function setBar(string $bar)
    {
    }
}
