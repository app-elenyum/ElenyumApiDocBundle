<?php

namespace Elenyum\ApiDocBundle\Repository;

use Elenyum\ApiDocBundle\Util\Paginator;
use Elenyum\ApiDocBundle\Util\RestParams;

interface PaginatorInterface
{
    public function getPaginator(
        RestParams $params
    ): Paginator;
}