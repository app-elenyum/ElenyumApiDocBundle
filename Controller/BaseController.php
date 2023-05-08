<?php

namespace Elenyum\ApiDocBundle\Controller;

use Elenyum\ApiDocBundle\Annotation\Access;
use Elenyum\ApiDocBundle\Entity\BaseEntity;
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

    /**
     * @param BaseEntity $entity
     * @param string $type - 'get', 'post', 'put', 'delete'
     * @param string $message
     * @return void
     */
    public function checkAccess(BaseEntity $entity, string $type, string $message = 'Access Denied.'): void
    {
        $access = (new \ReflectionClass($entity))->getAttributes(Access::class);
        if (!empty($access) && !empty($access[0])) {
            $access = $access[0];
            /** @var Access $instance */
            $instance = $access->newInstance();
            if ($instance instanceof Access && !$instance->isAllow($this->getUser(), $type, $entity)) {
                $exception = $this->createAccessDeniedException($message);
                $exception->setAttributes($type);
                $exception->setSubject($entity);

                throw $exception;
            }
        }
    }
}