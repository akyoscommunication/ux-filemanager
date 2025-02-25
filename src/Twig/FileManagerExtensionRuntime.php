<?php

namespace Akyos\UXFileManager\Twig;

use Akyos\UXFileManager\Enum\Mimes;
use Akyos\UXFileManager\Repository\FileRepository;
use Akyos\UXFileManager\Security\Voter\FileManagerVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;
use Akyos\UXFileManager\Entity\File;

class FileManagerExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private array $config,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
        private FileRepository $fileRepository,
        private RequestStack $requestStack,
    ) {}

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getConfigurationFromKey(string $key): array
    {
        if (!array_key_exists($key, $this->getConfig()['paths'])) {
            throw new \RuntimeException('Configuration not found');
        }

        return $this->getConfig()['paths'][$key];
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

    public function getFrontendFile(mixed $value): array
    {
        if (!($value instanceof File)) {
            $value = $this->fileRepository->find($value);
        }

        if (!$value) {
            return [];
        }

        $path = $value->getPath();
        $splInfo = new \SplFileInfo($path);

        return [
            'id' => $value->getId(),
            'path' => $path,
            'name' => $splInfo->getFilename(),
            'size' => $splInfo->getSize(),
            'sizeHuman' => $this->bytesToHuman($splInfo->getSize()),
            'mime' => mime_content_type($path),
            'icon' => $this->getMimeIcon($splInfo),
            'alt' => $value->getAlt(),
        ];
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

    public function relativePath(string $path, string $key): string
    {
        return str_replace($this->getConfig()['paths'][$key]['path'], '', $path);
    }

    // TODO: mettre toutes les methodes suivantes dans un service dédié
    public function getFile(string $path, bool $save = false): File
    {
        $file = $this->fileRepository->findOneBy(['path' => $path]);

        if (!$file) {
            $file = new File();
            $file
                ->setPath($path)
                ->setMime(Mimes::from(mime_content_type($path)))
            ;
        }

        if ($save) {
            $this->fileRepository->save($file, true);
        }

        return $file;
    }

    public function managePathInDirectory(string $oldPath, string $newPath): void
    {
        $files = $this->fileRepository->findFileInDirectory($oldPath)->getQuery()->getResult();

        foreach ($files as $file) {
            $file->setPath(str_replace($oldPath, $newPath, $file->getPath()));
            $this->fileRepository->save($file, true);
        }
    }

    public function managePath(string $oldPath, ?string $newPath = null): File
    {
        $file = $this->getFile($oldPath);

        $pathToSave = $newPath ?? $oldPath;

        $file->setPath($pathToSave);

        $this->fileRepository->save($file, true);

        return $file;
    }

    public function deleteFile(string $path): void
    {
        $file = $this->getFile($path);

        if ($file->getId()) {
            $this->fileRepository->remove($file, true);
        }
    }

    public function setAlt(string $path, string $alt): void
    {
        $file = $this
            ->getFile($path)
            ->setAlt($alt)
        ;

        $this->fileRepository->save($file, true);
    }

    public function upload(FormInterface $form): ?File
    {
        $formConfig = $form->getConfig()->getOptions();
        $data = $form->getData();
        $request = $this->requestStack->getCurrentRequest();
        $file = $data['file'];

        // if isDeleted, then just set to nul for Entity
        if ($data['isDeleted']) {
            return null;
        }

        // UX Live component case
        if (!$file) {
            if (!empty($request->files->all())) {
                $keys = $this->getArrayOfParentForm($form);
                $current = $request->files->all();
                foreach ($keys as $key) {
                    if (!array_key_exists($key, $current)) {
                        $current = null;
                    } else {
                        $current = $current[$key];
                    }
                }
                $file = $current;
            }
        }

        if (is_array($file) && array_key_exists('file', $file)) {
            $file = $file['file'];
        }

        if (!$file && !$data['isDeleted']) {
            return $data['file_db'];
        }

        $config = $this->getConfigurationFromKey($formConfig['path']);

        $formConfig['dir'] = trim($formConfig['dir'], '/');

        $pathToDir = $config['path'] . '/' . $formConfig['dir'];

        $newPath = $pathToDir . '/' . $file->getClientOriginalName();
        $file->move($pathToDir, $file->getClientOriginalName());
        return $this->managePath($newPath);
    }

    private function getArrayOfParentForm(Form $form, array $array = []): array
    {
        // put at beginning
        array_unshift($array, $form->getName());
        if ($form->getParent()) {
            return $this->getArrayOfParentForm($form->getParent(), $array);
        }

        return $array;
    }
}
