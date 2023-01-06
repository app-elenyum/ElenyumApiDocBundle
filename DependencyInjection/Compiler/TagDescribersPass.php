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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class TagDescribersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('elenyum_api_doc.describer') as $id => $tags) {
            $describer = $container->getDefinition($id);
            foreach ($container->getParameter('elenyum_api_doc.areas') as $area) {
                foreach ($tags as $tag) {
                    $describer->addTag(sprintf('elenyum_api_doc.describer.%s', $area), $tag);
                }
            }
        }
    }
}
