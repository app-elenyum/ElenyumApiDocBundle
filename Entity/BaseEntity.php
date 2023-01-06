<?php

namespace Elenyum\ApiDocBundle\Entity;

use Exception;
use ReflectionClass;
use Symfony\Component\Serializer\Annotation\Groups;

abstract class BaseEntity implements BaseEntityInterface
{
    /**
     * @throws Exception
     */
    public function toArray(string $groupName): array
    {
        $reflectionClass = new ReflectionClass($this);
        $result = [];
        foreach ($reflectionClass->getProperties() as $property) {
            $group = $property->getAttributes(Groups::class);
            if (end($group) !== false && in_array($groupName, current(end($group)?->getArguments()))) {
                $methodVal = 'get' . ucfirst($property->getName());
                if (method_exists($this, $methodVal)) {
                    $result[$property->getName()] = $this->{$methodVal}();
                } else {
                    throw new Exception('Undefined method: '. $methodVal . ' for class: '. $this::class);
                }
            }
        }
        if (empty($result)) {
            throw new Exception('Not found group for "'.$groupName.'" by entity: '.$this::class);
        }

        return $result;
    }
}