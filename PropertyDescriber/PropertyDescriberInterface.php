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

use OpenApi\Annotations\Schema;
use Symfony\Component\PropertyInfo\Type;

interface PropertyDescriberInterface
{
    /**
     * @param Type[] $types
     */
    public function describe(array $types, Schema $property, array $groups = null);

    /**
     * @param Type[] $types
     */
    public function supports(array $types): bool;
}
