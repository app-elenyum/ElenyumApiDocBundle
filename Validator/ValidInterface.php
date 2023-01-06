<?php

namespace Elenyum\ApiDocBundle\Validator;

interface ValidInterface
{
    /**
     * @param object $entity
     * @return bool
     */
    public function isValid(object $entity): bool;

    /**
     * @return array
     */
    public function getMessages(): array;
}