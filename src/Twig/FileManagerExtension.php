<?php

namespace Akyos\UXFileManager\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FileManagerExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('ux_filemanager_render', [FileManagerExtensionRuntime::class, 'render']),
            new TwigFilter('ux_filemanager_file', [FileManagerExtensionRuntime::class, 'getFrontendFile']),
            new TwigFilter('dirname', 'dirname'),
            new TwigFilter('strpos', 'strpos'),
            // filter that convert bytes to human readable format
            new TwigFilter('bytes_to_human', [FileManagerExtensionRuntime::class, 'bytesToHuman']),
            new TwigFilter('mime_icon', [FileManagerExtensionRuntime::class, 'getMimeIcon']),
            // filter that return the relative path of the file
            new TwigFilter('relative_path', [FileManagerExtensionRuntime::class, 'relativePath']),
        ];
    }
}
