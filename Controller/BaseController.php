<?php

namespace Elenyum\ApiDocBundle\Controller;

use Elenyum\ApiDocBundle\Util\RestParams;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class BaseController extends SymfonyAbstractController
{
    /**
     * @throws \JsonException
     */
    protected function getRestParams(Request $request): RestParams
    {
        $queryParams = $request->query->all();
        $offset = $queryParams['offset'] ?? 0;
        $limit = $queryParams['limit'] ?? 10;
        $filter = isset($queryParams['filter']) ? json_decode(
            $queryParams['filter'],
            JSON_OBJECT_AS_ARRAY,
            512,
            JSON_THROW_ON_ERROR
        ) : [];
        $sort = isset($queryParams['sort']) ? explode(',', $queryParams['sort']) : [];
        $field = isset($queryParams['field']) ? explode(',', $queryParams['field'] ?? '') : [];

        return new RestParams($offset, $limit, $filter, $sort, $field);
    }
}