<?php

namespace Akyos\UXFileManager\DependencyInjection\Compiler;

use Akyos\UXFileManager\Twig\FileManagerExtensionRuntime;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UXFileManagerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $config = $container->getExtensionConfig('ux_file_manager')[0];

        $runtimeDefinition = $container->findDefinition(FileManagerExtensionRuntime::class);
        $runtimeDefinition->setArgument(0, $config);
    }
}
