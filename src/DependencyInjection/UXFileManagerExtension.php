<?php

namespace Akyos\UXFileManager\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Akyos\UXFileManager\Attributes\AsUXFileManagerAction;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class UXFileManagerExtension extends Extension implements PrependExtensionInterface, ConfigurationInterface
{
    public function prepend(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['TwigBundle'])) {
            $container->prependExtensionConfig('twig', ['form_themes' => ['@UXFileManager/form.html.twig']]);
        }

        if (isset($bundles['DoctrineBundle'])) {
            $container->prependExtensionConfig('doctrine', [
                'orm' => [
                    'mappings' => [
                        'UXFileManagerBundle' => [
                            'is_bundle' => true,
                            'type' => 'attribute',
                            'dir' => 'src/Entity',
                            'prefix' => 'Akyos\\UXFileManager\\Entity',
                            'alias' => 'UXFileManager',
                        ],
                    ],
                ],
            ]);
        }

        if ($this->isAssetMapperAvailable($container)) {
            $container->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [
                        __DIR__.'/../../assets/dist' => '@akyoscommunication/ux-filemanager',
                    ],
                ],
                'translator' => [
                    'paths' => [
                        __DIR__.'/../../translations',
                    ],
                ],
            ]);
        }
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        try {
            $loader->load('services.yaml');
        } catch (\Exception $e) {
            dd($e);
        }

        $container->registerAttributeForAutoconfiguration(
            AsUXFileManagerAction::class,
            static function (ChildDefinition $definition, AsUXFileManagerAction $attribute, \ReflectionMethod $reflector) {
                $definition->addTag('ux_file_manager.action', [
                    ...$attribute->serviceConfig(),
                    'method' => $reflector->getName(),
                    'class' => $reflector->getDeclaringClass()->getName(),
                ]);

                $definition->setPublic(true);
            }
        );
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ux_filemanager');
        $rootNode = $treeBuilder->getRootNode();
        \assert($rootNode instanceof ArrayNodeDefinition);

        $rootNode
            ->children()
                ->arrayNode('paths')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('path')->end()
                            ->scalarNode('security')->end()
                            ->scalarNode('name')->end()
                        ->end()
                    ->end()
                ->end()
        ;

        return $treeBuilder;
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return $this;
    }
}
