<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\DependencyInjection;

use FOS\RestBundle\Controller\Annotations\ParamInterface;
use JMS\Serializer\ContextFactory\SerializationContextFactoryInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Elenyum\ApiDocBundle\ApiDocGenerator;
use Elenyum\ApiDocBundle\Describer\ExternalDocDescriber;
use Elenyum\ApiDocBundle\Describer\OpenApiPhpDescriber;
use Elenyum\ApiDocBundle\Describer\RouteDescriber;
use Elenyum\ApiDocBundle\ModelDescriber\BazingaHateoasModelDescriber;
use Elenyum\ApiDocBundle\ModelDescriber\JMSModelDescriber;
use Elenyum\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use Elenyum\ApiDocBundle\Routing\FilteredRouteCollectionBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Routing\RouteCollection;

final class ElenyumApiDocExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('framework', ['property_info' => ['enabled' => true]]);

        $bundles = $container->getParameter('kernel.bundles');

        // JMS Serializer support
        if (isset($bundles['JMSSerializerBundle'])) {
            $container->prependExtensionConfig('elenyum_api_doc', ['models' => ['use_jms' => true]]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.xml');

        // Filter routes
        $routesDefinition = (new Definition(RouteCollection::class))
            ->setFactory([new Reference('router'), 'getRouteCollection']);

        $container->setParameter('elenyum_api_doc.areas', array_keys($config['areas']));
        $container->setParameter('elenyum_api_doc.media_types', $config['media_types']);
        $container->setParameter('elenyum_api_doc.use_validation_groups', $config['use_validation_groups']);
        foreach ($config['areas'] as $area => $areaConfig) {
            $nameAliases = $this->findNameAliases($config['models']['names'], $area);
            $container->register(sprintf('elenyum_api_doc.generator.%s', $area), ApiDocGenerator::class)
                ->setPublic(true)
                ->addMethodCall('setAlternativeNames', [$nameAliases])
                ->addMethodCall('setMediaTypes', [$config['media_types']])
                ->addMethodCall('setLogger', [new Reference('logger')])
                ->addTag('monolog.logger', ['channel' => 'elenyum_api_doc'])
                ->setArguments([
                    new TaggedIteratorArgument(sprintf('elenyum_api_doc.describer.%s', $area)),
                    new TaggedIteratorArgument('elenyum_api_doc.model_describer'),
                ]);

            $container->register(sprintf('elenyum_api_doc.describers.route.%s', $area), RouteDescriber::class)
                ->setPublic(false)
                ->setArguments([
                    new Reference(sprintf('elenyum_api_doc.routes.%s', $area)),
                    new Reference('elenyum_api_doc.controller_reflector'),
                    new TaggedIteratorArgument('elenyum_api_doc.route_describer'),
                ])
                ->addTag(sprintf('elenyum_api_doc.describer.%s', $area), ['priority' => -400]);

            $container->register(sprintf('elenyum_api_doc.describers.openapi_php.%s', $area), OpenApiPhpDescriber::class)
                ->setPublic(false)
                ->setArguments([
                    new Reference(sprintf('elenyum_api_doc.routes.%s', $area)),
                    new Reference('elenyum_api_doc.controller_reflector'),
                    new Reference('annotations.reader'), // We cannot use the cached version of the annotation reader since the construction of the annotations is context dependant...
                    new Reference('logger'),
                ])
                ->addTag(sprintf('elenyum_api_doc.describer.%s', $area), ['priority' => -200]);

            $container->register(sprintf('elenyum_api_doc.describers.config.%s', $area), ExternalDocDescriber::class)
                ->setPublic(false)
                ->setArguments([
                    $areaConfig['documentation'],
                    true,
                ])
                ->addTag(sprintf('elenyum_api_doc.describer.%s', $area), ['priority' => 990]);

            unset($areaConfig['documentation']);
            if (0 === count($areaConfig['path_patterns'])
                && 0 === count($areaConfig['host_patterns'])
                && 0 === count($areaConfig['name_patterns'])
                && false === $areaConfig['with_annotation']
                && false === $areaConfig['disable_default_routes']
            ) {
                $container->setDefinition(sprintf('elenyum_api_doc.routes.%s', $area), $routesDefinition)
                    ->setPublic(false);
            } else {
                $container->register(sprintf('elenyum_api_doc.routes.%s', $area), RouteCollection::class)
                    ->setPublic(false)
                    ->setFactory([
                        (new Definition(FilteredRouteCollectionBuilder::class))
                            ->setArguments(
                                [
                                    new Reference('annotation_reader'), // Here we use the cached version as we don't deal with @OA annotations in this service
                                    new Reference('elenyum_api_doc.controller_reflector'),
                                    $area,
                                    $areaConfig,
                                ]
                            ),
                        'filter',
                    ])
                    ->addArgument($routesDefinition);
            }
        }

        $container->register('elenyum_api_doc.generator_locator', ServiceLocator::class)
            ->setPublic(false)
            ->addTag('container.service_locator')
            ->addArgument(array_combine(
                array_keys($config['areas']),
                array_map(function ($area) { return new Reference(sprintf('elenyum_api_doc.generator.%s', $area)); }, array_keys($config['areas']))
            ));

        $container->getDefinition('elenyum_api_doc.model_describers.object')
            ->setArgument(3, $config['media_types']);

        // Add autoconfiguration for model describer
        $container->registerForAutoconfiguration(ModelDescriberInterface::class)
            ->addTag('elenyum_api_doc.model_describer');

        // Import services needed for each library
        $loader->load('php_doc.xml');

        if (interface_exists(ParamInterface::class)) {
            $loader->load('fos_rest.xml');
            $container->getDefinition('elenyum_api_doc.route_describers.fos_rest')
                ->setArgument(1, $config['media_types']);
        }

        $bundles = $container->getParameter('kernel.bundles');
        if (!isset($bundles['TwigBundle']) || !class_exists('Symfony\Component\Asset\Packages')) {
            $container->removeDefinition('elenyum_api_doc.controller.swagger_ui');

            $container->removeDefinition('elenyum_api_doc.render_docs.html');
            $container->removeDefinition('elenyum_api_doc.render_docs.html.asset');
        }

        // ApiPlatform support
        if (isset($bundles['ApiPlatformBundle']) && class_exists('ApiPlatform\Documentation\Documentation')) {
            $loader->load('api_platform.xml');
        }

        // JMS metadata support
        if ($config['models']['use_jms']) {
            $jmsNamingStrategy = interface_exists(SerializationVisitorInterface::class) ? null : new Reference('jms_serializer.naming_strategy');
            $contextFactory = interface_exists(SerializationContextFactoryInterface::class) ? new Reference('jms_serializer.serialization_context_factory') : null;

            $container->register('elenyum_api_doc.model_describers.jms', JMSModelDescriber::class)
                ->setPublic(false)
                ->setArguments([
                    new Reference('jms_serializer.metadata_factory'),
                    new Reference('annotations.reader'),
                    $config['media_types'],
                    $jmsNamingStrategy,
                    $container->getParameter('elenyum_api_doc.use_validation_groups'),
                    $contextFactory,
                ])
                ->addTag('elenyum_api_doc.model_describer', ['priority' => 50]);

            // Bazinga Hateoas metadata support
            if (isset($bundles['BazingaHateoasBundle'])) {
                $container->register('elenyum_api_doc.model_describers.jms.bazinga_hateoas', BazingaHateoasModelDescriber::class)
                    ->setDecoratedService('elenyum_api_doc.model_describers.jms', 'elenyum_api_doc.model_describers.jms.inner')
                    ->setPublic(false)
                    ->setArguments([
                        new Reference('hateoas.configuration.metadata_factory'),
                        new Reference('elenyum_api_doc.model_describers.jms.inner'),
                    ]);
            }
        } else {
            $container->removeDefinition('elenyum_api_doc.model_describers.object_fallback');
        }

        // Import the base configuration
        $container->getDefinition('elenyum_api_doc.describers.config')->replaceArgument(0, $config['documentation']);

        // Compatibility Symfony
        $controllerNameConverter = null;
        if ($container->hasDefinition('.legacy_controller_name_converter')) { // 4.4
            $controllerNameConverter = $container->getDefinition('.legacy_controller_name_converter');
        } elseif ($container->hasDefinition('controller_name_converter')) { // < 4.4
            $controllerNameConverter = $container->getDefinition('controller_name_converter');
        }

        if (null !== $controllerNameConverter) {
            $container->getDefinition('elenyum_api_doc.controller_reflector')->setArgument(1, $controllerNameConverter);
        }
    }

    private function findNameAliases(array $names, string $area): array
    {
        $nameAliases = array_filter($names, function (array $aliasInfo) use ($area) {
            return empty($aliasInfo['areas']) || in_array($area, $aliasInfo['areas'], true);
        });

        $aliases = [];
        foreach ($nameAliases as $nameAlias) {
            $aliases[$nameAlias['alias']] = [
                'type' => $nameAlias['type'],
                'groups' => $nameAlias['groups'],
            ];
        }

        return $aliases;
    }
}
