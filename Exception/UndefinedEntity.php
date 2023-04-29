<?php

namespace Elenyum\ApiDocBundle\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @property $translator
 */
class UndefinedEntity extends Exception
{
    public function __construct(string $entity, null|int|string $id = null, int $code = Response::HTTP_UNPROCESSABLE_ENTITY, ?Throwable $previous = null)
    {
        if ($id !== null) {
            $id = ' by id: '.$id;
        } else {
            $id = '';
        }
        $message = 'Undefined entity ' . $entity . $id;
        parent::__construct($message, $code, $previous);
    }
}