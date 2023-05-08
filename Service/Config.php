<?php

namespace Elenyum\ApiDocBundle\Service;

use Elenyum\ApiDocBundle\Validator\ValidInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\Attribute\Required;

class Config implements ConfigInterface
{
    #[Required]
    public LoggerInterface $logger;

    #[Required]
    public ManagerRegistry $registry;

    #[Required]
    public ParameterBagInterface $parameterBag;

    #[Required]
    public ValidInterface $validator;

    #[Required]
    public EventDispatcherInterface $eventDispatcher;

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
}