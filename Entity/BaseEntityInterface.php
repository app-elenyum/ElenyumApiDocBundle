<?php

namespace Elenyum\ApiDocBundle\Entity;

interface BaseEntityInterface
{
    public function toArray(string $groupName): array;
}