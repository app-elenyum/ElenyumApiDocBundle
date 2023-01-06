<?php

namespace Elenyum\ApiDocBundle\Service;

use Elenyum\ApiDocBundle\Validator\Valid;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Exception;

interface BaseServiceInterface
{
    /**
     * @throws \Exception
     */
    public function getRepository(?string $entity = null): ?EntityRepository;

    /**
     * return validator
     *
     * @return Valid
     */
    public function getValidator(): Valid;

    /**
     * @throws Exception
     */
    public function toEntity(mixed $data): object;

    /**
     * @throws Exception
     */
    public function updateEntity(object $entity, string $data): object;

    /**
     * @return ObjectManager
     */
    public function getEntityManager(): ObjectManager;
}