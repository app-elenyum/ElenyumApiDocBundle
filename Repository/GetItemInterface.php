<?php

namespace Elenyum\ApiDocBundle\Repository;

use Elenyum\ApiDocBundle\Entity\BaseEntity;

interface GetItemInterface
{
    public function getItem(int $id): ?BaseEntity;
}