<?php

namespace Akyos\UXFileManager\Twig\Components;

use Akyos\UXFileManager\Entity\File;
use Akyos\UXFileManager\Enum\Mimes;
use Akyos\UXFileManager\Repository\FileRepository;
use Akyos\UXFileManager\Twig\FileManagerExtensionRuntime;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent('UX:FileManager:render', template: '@UXFileManager/twig_components/render.html.twig')]
final class Render
{
    public mixed $value;

    public function __construct(
        private FileManagerExtensionRuntime $fileManagerExtensionRuntime,
        private FileRepository $fileRepository,
    ) {}

    #[ExposeInTemplate('file')]
    public function getFile(): File
    {
        if ($this->value instanceof File) {
            return $this->value;
        }

        if (is_string($this->value)) {
            return $this->fileManagerExtensionRuntime->getFile($this->value, true);
        }

        if (is_int($this->value)) {
            return $this->fileRepository->find($this->value);
        }

        throw new \RuntimeException('Value must be a string or an integer');
    }

    #[ExposeInTemplate('is_embeded')]
    public function isEmbeded(): bool
    {
        return Mimes::isEmbed($this->getFile()->getMime());
    }
}
