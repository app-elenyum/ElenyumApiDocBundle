<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\DependencyInjection\Compiler;

use Elenyum\ApiDocBundle\ModelDescriber\FormModelDescriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Enables the FormModelDescriber only if forms are enabled.
 *
 * @internal
 */
final class ConfigurationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('form.factory')) {
            $container->register('elenyum_api_doc.model_describers.form', FormModelDescriber::class)
                ->setPublic(false)
                ->addArgument(new Reference('form.factory'))
                ->addArgument(new Reference('annotations.reader'))
                ->addArgument($container->getParameter('elenyum_api_doc.media_types'))
                ->addArgument($container->getParameter('elenyum_api_doc.use_validation_groups'))
                ->addTag('elenyum_api_doc.model_describer', ['priority' => 100]);
        }

        $container->getParameterBag()->remove('elenyum_api_doc.media_types');
    }
}
