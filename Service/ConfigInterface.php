<?php

namespace Elenyum\ApiDocBundle\Service;

use Doctrine\Persistence\ManagerRegistry;
use Elenyum\ApiDocBundle\Validator\ValidInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Serializer;

interface ConfigInterface
{
    /**
     * @return ManagerRegistry
     */
    public function getRegistry(): ManagerRegistry;

    /**
     * @return ParameterBagInterface
     */
    public function getParameterBag(): ParameterBagInterface;

    /**
     * @return ValidInterface
     */
    public function getValidator(): ValidInterface;

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface;

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface;

    /**
     * @return Serializer
     */
    public function getSerializer(): Serializer;
}