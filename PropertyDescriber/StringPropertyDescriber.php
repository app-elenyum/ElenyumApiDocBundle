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

class StringPropertyDescriber implements PropertyDescriberInterface
{
    use NullablePropertyTrait;

    public function describe(array $types, OA\Schema $property, array $groups = null)
    {
        $property->type = 'string';
        $this->setNullableProperty($types[0], $property);
    }

    public function supports(array $types): bool
    {
        return 1 === count($types) && Type::BUILTIN_TYPE_STRING === $types[0]->getBuiltinType();
    }
}
