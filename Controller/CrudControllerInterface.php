<?php

namespace Elenyum\ApiDocBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface CrudControllerInterface
{
    /**
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response;

    /**
     * @param int $id
     * @return Response
     */
    public function get(int $id): Response;

    /**
     * @param Request $request
     * @return Response
     */
    public function post(Request $request): Response;

    /**
     * @param int $id
     * @param Request $request
     * @return Response
     */
    public function put(int $id, Request $request): Response;

    /**
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response;
}