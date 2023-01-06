<?php

namespace Elenyum\ApiDocBundle\Service;

use Elenyum\ApiDocBundle\Validator\Valid;
use Elenyum\ApiDocBundle\Validator\ValidationException;
use Elenyum\ApiDocBundle\Validator\ValidInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Service\Attribute\Required;

abstract class BaseService implements BaseServiceInterface
{
    protected const DATABASE = 'default';
    protected const ENTITY = null;

    private ManagerRegistry $registry;
    private ParameterBagInterface $parameterBag;
    private ValidInterface $validator;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;
    private Serializer $serializer;
    private ConfigInterface $config;

    #[Required]
    public function setConfig(ConfigInterface $config): self
    {
        $this->config = $config;
        $this->registry = $config->getRegistry();
        $this->parameterBag = $config->getParameterBag();
        $this->validator = $config->getValidator();
        $this->eventDispatcher = $config->getEventDispatcher();
        $this->logger = $config->getLogger();
        $this->serializer = $config->getSerializer();

        return $this;
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * Возвращает entity manager
     *
     * @return ObjectManager
     */
    public function getEntityManager(): ObjectManager
    {
        return $this->registry->getManager(static::DATABASE);
    }

    /**
     * @throws \Exception
     */
    public function getRepository(?string $entity = null): ?EntityRepository
    {
        $entity = $entity ?? static::ENTITY;
        if ($entity === null) {
            throw new Exception('Entity name is empty');
        }
        $repository = $this->getEntityManager()->getRepository($entity);
        if ($repository instanceof EntityRepository) {
            return $repository;
        }

        return null;
    }

    /**
     * return validator
     *
     * @return Valid
     */
    public function getValidator(): Valid
    {
        return $this->validator;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log(mixed $level, string $message, array $context = []): void
    {
        $message = 'CLASS::'.$this::class.'::'.$message;
        $this->logger->log($level, $message, $context);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function isDev(): bool
    {
        return $this->parameterBag->get('app.environment') === 'dev';
    }

    /**
     * @throws Exception
     */
    public function toEntity(mixed $data): object
    {
        $entity = $this->serializer->deserialize($data, static::ENTITY, 'json');
        $validator = $this->getValidator();
        if (!$validator->isValid($entity)) {
            throw new ValidationException(json_encode($validator->getMessages()), Response::HTTP_BAD_REQUEST);
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @return mixed
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function toArray(object $entity): mixed
    {
        return $this->serializer->normalize($entity, null);
    }

    /**
     * @throws Exception
     */
    public function updateEntity(object $entity, string $data): object
    {
        $entity = $this->serializer->deserialize(
            $data,
            get_class($entity),
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $entity]
        );
        $validator = $this->validator;
        if (!$validator->isValid($entity)) {
            throw new ValidationException(json_encode($validator->getMessages()), Response::HTTP_BAD_REQUEST);
        }

        return $entity;
    }
}