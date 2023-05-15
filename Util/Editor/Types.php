<?php

namespace Elenyum\ApiDocBundle\Util\Editor;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

class Types
{
    const SIMPLE = [
        'id' => 'id',
        'string' => 'string',
        'guid' => 'string',
        'text' => 'string',
        'integer' => 'int',
        'float' => 'float',
        'boolean' => 'bool',
        'date_immutable' => DateTimeImmutable::class,
        'time_immutable' => DateTimeImmutable::class,
        'datetime_immutable' => DateTimeImmutable::class,
        'object' => 'array',
        'array' => 'array',
    ];

    const OBJECT = [
        'ManyToOne' => ManyToOne::class,
        'OneToOne' => OneToOne::class,
//        'OneToMany' => OneToMany::class,
        'ManyToMany' => ManyToMany::class,
    ];

    public static function getAll(): array
    {
        return array_merge(Types::SIMPLE, Types::OBJECT);
    }
}