<?php

namespace Akyos\UXFileManager\Twig;

use Akyos\UXFileManager\Enum\Mimes;
use Akyos\UXFileManager\Security\Voter\FileManagerVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class FileManagerExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private array $config = [],
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
    ) {}

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getConfigurationKey(string $path): string
    {
        // check if the path is a file
        if (!is_dir($path)) {
            $path = dirname($path);
        }

        $availablePaths = array_map(fn($p) => $p['path'], $this->getConfig()['paths']);

        // if the path is the root path
        if (in_array($path, $availablePaths, true)) {
            return array_search($path, $availablePaths, true);
        }

        // check for all paths, if on of them is the parent of the file
        $pathFilter = array_filter($availablePaths, fn($availablePath) => str_starts_with($path, $availablePath));
        if (empty($pathFilter) || count($pathFilter) > 1) {
            throw new \RuntimeException('Path not found');
        }

        return array_search(reset($pathFilter), $availablePaths, true);
    }

    public function getConfiguration(string $path): array
    {
        $keyOfConfiguration = $this->getConfigurationKey($path);

        return $this->getConfig()['paths'][$keyOfConfiguration];
    }

    public function render($path)
    {
        if (!$this->security->isGranted(FileManagerVoter::VIEW, $path)) {
            return null;
        }

        $keyOfConfiguration = $this->getConfigurationKey($path);
        $configuration = $this->getConfiguration($path);
        $path = str_replace($configuration['path'], '', $path);

        return $this->urlGenerator->generate('ux.file_manager.render', ['path' => $path, 'configurationKey' => $keyOfConfiguration]);
    }

    public function bytesToHuman($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getMimeIcon(\SplFileInfo $file): string
    {
        $mime = mime_content_type($file->getPathname());
        return Mimes::from($mime)->getIcon();
    }

    public function splFileInfo(string $path): \SplFileInfo
    {
        return new \SplFileInfo($path);
    }

    public function relativePath(string $path, string $key): string
    {
        $path = str_replace($this->getConfig()['paths'][$key]['path'], '', $path);
        return ltrim($path, '/');
    }
}
