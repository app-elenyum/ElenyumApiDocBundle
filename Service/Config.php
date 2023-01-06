<?php

namespace Elenyum\ApiDocBundle\Service;

use Elenyum\ApiDocBundle\Validator\ValidInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Service\Attribute\Required;

class Config implements ConfigInterface
{
    #[Required]
    public LoggerInterface $logger;

    public Serializer $serializer;

    #[Required]
    public ManagerRegistry $registry;

    #[Required]
    public ParameterBagInterface $parameterBag;

    #[Required]
    public ValidInterface $validator;

    #[Required]
    public EventDispatcherInterface $eventDispatcher;

    public function __construct()
    {
        $normalizer = new GetSetMethodNormalizer();
        $encoder = new JsonEncoder();

        $this->serializer = new Serializer([$normalizer], [$encoder]);
    }

    /**
     * @return ManagerRegistry
     */
    public function getRegistry(): ManagerRegistry
    {
        return $this->registry;
    }

    /**
     * @return ParameterBagInterface
     */
    public function getParameterBag(): ParameterBagInterface
    {
        return $this->parameterBag;
    }

    /**
     * @return ValidInterface
     */
    public function getValidator(): ValidInterface
    {
        return $this->validator;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return Serializer
     */
    public function getSerializer(): Serializer
    {
        return $this->serializer;
    }
}