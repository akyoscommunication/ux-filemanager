<?php

namespace Akyos\UXFileManager\DependencyInjection\Compiler;

use Akyos\UXFileManager\Twig\Components\Item;
use Akyos\UXFileManager\Twig\FileManagerExtensionRuntime;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class UXFileManagerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $config = $container->getExtensionConfig('ux_file_manager')[0];

        $runtimeDefinition = $container->findDefinition(FileManagerExtensionRuntime::class);
        $runtimeDefinition->setArgument(0, $config);

        $fileManagerDefinition = $container->findDefinition(Item::class);
        $actions = [];

        foreach ($container->findTaggedServiceIds('ux_file_manager.action') as $id => $tags) {
            $actions[] = [  
                'method' => $tags[0]['method'],
                'label' => $tags[0]['label'],
                'icon' => $tags[0]['icon'],
                'class' => $tags[0]['class'],
            ];
        }

        $fileManagerDefinition->setArgument(0, $actions);
    }
}
