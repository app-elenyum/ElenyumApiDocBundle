<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Entity;

use DateTimeImmutable;
use Exception;
use ReflectionClass;
use Symfony\Component\Serializer\Annotation\Groups;

abstract class BaseEntity implements BaseEntityInterface
{
    public const TYPE_GET = 'GET';
    public const TYPE_LIST = 'LIST';
    public const TYPE_POST_RES = 'POST_RES';
    public const TYPE_POST_REQ = 'POST_REQ';
    public const TYPE_PUT_RES = 'PUT_RES';
    public const TYPE_PUT_REQ = 'PUT_REQ';
    public const TYPE_DEL_RES = 'DEL_RES';

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
                    $val = $this->{$methodVal}();
                    if ($val instanceof DateTimeImmutable) {
                        $val = $val->format(DATE_ATOM);
                    }
                    $result[$property->getName()] = $val;
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