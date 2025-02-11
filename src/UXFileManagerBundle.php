<?php

namespace Akyos\UXFileManager;

use Akyos\UXFileManager\DependencyInjection\Compiler\UXFileManagerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class UXFileManagerBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
