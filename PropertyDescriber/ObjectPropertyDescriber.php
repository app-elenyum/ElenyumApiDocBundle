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

use Elenyum\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Elenyum\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Elenyum\ApiDocBundle\Model\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\PropertyInfo\Type;

class ObjectPropertyDescriber implements PropertyDescriberInterface, ModelRegistryAwareInterface
{
    use ModelRegistryAwareTrait;

    public function describeObject(array $types, OA\Schema $property, array $groups = null)
    {
        $type = new Type(
            $types[0]->getBuiltinType(),
            false,
            $types[0]->getClassName(),
            $types[0]->isCollection(),
            // BC layer for symfony < 5.3
            method_exists($types[0], 'getCollectionKeyTypes') ? $types[0]->getCollectionKeyTypes() : $types[0]->getCollectionKeyType(),
            method_exists($types[0], 'getCollectionValueTypes') ?
                ($types[0]->getCollectionValueTypes()[0] ?? null) :
                $types[0]->getCollectionValueType()
        ); // ignore nullable field

        if ($types[0]->isNullable()) {
            $property->nullable = true;
            $property->allOf = [new OA\Schema(['ref' => $this->modelRegistry->register(new Model($type, $groups))])];

            return;
        }

        $property->ref = $this->modelRegistry->register(new Model($type, $groups));
    }

    public function describe(array $types, OA\Schema $property, array $groups = null)
    {
        $groupTypes = array_map(function($item) {
            return explode("_", $item)[0];
        }, $groups);

        if (array_intersect(['POST', 'PUT'], $groupTypes)) {
            $property->example = '1';
            $property->description = 'enter id to parent entity for added';
        } else {
            $this->describeObject($types, $property, $groups);
        }
    }

    public function supports(array $types): bool
    {
        return 1 === count($types) && Type::BUILTIN_TYPE_OBJECT === $types[0]->getBuiltinType();
    }
}
