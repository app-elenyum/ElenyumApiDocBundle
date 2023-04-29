<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\PropertyDescriber;

use OpenApi\Annotations as OA;
use Symfony\Component\PropertyInfo\Type;

trait NullablePropertyTrait
{
    protected function setNullableProperty(Type $type, OA\Schema $property): void
    {
        if ($type->isNullable()) {
            $property->nullable = true;
        }
    }
}
