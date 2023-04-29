<?php

namespace Elenyum\ApiDocBundle\Validator;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ValidationException extends \Exception
{
    public function __construct(string $message = "", int $code = Response::HTTP_PRECONDITION_FAILED, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}