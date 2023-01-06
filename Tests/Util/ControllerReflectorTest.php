<?php

namespace Elenyum\ApiDocBundle\Tests\Util;

use Elenyum\ApiDocBundle\Tests\Functional\Controller\BazingaController;
use Elenyum\ApiDocBundle\Util\ControllerReflector;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\Container;

class ControllerReflectorTest extends TestCase
{
    public function testGetReflectionMethod(): void
    {
        $controllerReflector = new ControllerReflector(new Container());
        $this->assertEquals(
            ReflectionMethod::class,
            get_class($controllerReflector->getReflectionMethod([BazingaController::class, 'userAction']))
        );
        $this->assertEquals(
            ReflectionMethod::class,
            get_class($controllerReflector->getReflectionMethod(BazingaController::class.'::userAction'))
        );
        $this->assertNull(
            $controllerReflector->getReflectionMethod('UnknownController::userAction')
        );
        $this->assertNull($controllerReflector->getReflectionMethod(null));
    }
}
