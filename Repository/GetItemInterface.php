<?php

namespace Elenyum\ApiDocBundle\Repository;

interface GetItemInterface
{
    public function getItem(int $id): ?object;
}