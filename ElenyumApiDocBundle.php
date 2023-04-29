<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle;

use Elenyum\ApiDocBundle\DependencyInjection\Compiler\ConfigurationPass;
use Elenyum\ApiDocBundle\DependencyInjection\Compiler\TagDescribersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class ElenyumApiDocBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ConfigurationPass());
        $container->addCompilerPass(new TagDescribersPass());
    }
}
