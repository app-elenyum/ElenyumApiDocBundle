<?php

namespace Elenyum\ApiDocBundle\Repository;

interface GetItemForPutInterface
{
    public function getItemForPut(int $id): ?object;
}