<?php

namespace Elenyum\ApiDocBundle\Repository;

interface GetItemForDeleteInterface
{
    public function getItemsForDelete(array $ids): ?array;
}