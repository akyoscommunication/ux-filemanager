<?php

namespace Akyos\UXFileManager\Twig\Components;

use Akyos\UXFileManager\Security\Voter\FileManagerVoter;
use Akyos\UXFileManager\Twig\FileManagerExtensionRuntime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent('UX:FileManager', template: '@UXFileManager/components/UXFileManager.html.twig')]
final class UXFileManager extends AbstractController
{
    use DefaultActionTrait, ComponentToolsTrait;

    const VIEW_LIST = 'list';
    const VIEW_GRID = 'grid';

    #[LiveProp(writable: true)]
    public string $path;

    #[LiveProp(writable: true)]
    public ?string $dir = null;

    #[LiveProp(writable: true)]
    public string $newFolder = "";

    #[LiveProp(writable: true)]
    public string $q = "";

    #[LiveProp(writable: true)]
    public string $currentEditingPath = "";

    #[LiveProp(writable: true)]
    public string $currentEditingAlt = "";

    #[LiveProp(writable: true)]
    public array $selectedPaths = [];

    #[LiveProp(writable: true)]
    public string $bulkAction = "delete";

    #[LiveProp(writable: true)]
    public string $view = self::VIEW_LIST;

    #[LiveProp]
    public ?string $inputId = null;

    #[LiveProp(writable: true)]
    public string $orderBy = self::ORDER_BY_NAME;

    #[LiveProp(writable: true)]
    public string $orderByDirection = self::ORDER_BY_ASC;

    const ORDER_BY_NAME = 'name';
    const ORDER_BY_SIZE = 'size';
    const ORDER_BY_MTIME = 'mtime';

    const ORDER_BY_ASC = 'asc';
    const ORDER_BY_DESC = 'desc';

    const RECENTLY_USED_TOKEN = 'recently_used';

    public function __construct(
        private readonly FileManagerExtensionRuntime $fileManagerExtensionRuntime,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {}

    public function getOriginalPath(): string
    {
        return $this->fileManagerExtensionRuntime->getConfig()['paths'][$this->path]['path'];
    }

    #[ExposeInTemplate(self::RECENTLY_USED_TOKEN)]
    public function getRecentlyUsed(): array
    {
        return $this->requestStack->getSession()->get(self::RECENTLY_USED_TOKEN, []);
    }

    #[ExposeInTemplate('otherSpaces')]
    public function getOtherSpaces(): array
    {
        $otherSpaces = [];

        foreach ($this->fileManagerExtensionRuntime->getConfig()['paths'] as $key => $path) {
            if ($path['path'] === $this->getOriginalPath()) {
                continue;
            }

            $otherSpaces[$key] = $path;
        }

        return $otherSpaces;
    }

    #[ExposeInTemplate('realPath')]
    public function realPath(): string
    {
        return $this->getOriginalPath() . $this->dir;
    }

    #[PreReRender]
    public function prererender()
    {
        $this->denyAccessUnlessGranted(FileManagerVoter::VIEW, $this->realPath());
    }

    #[ExposeInTemplate('backFolder')]
    public function getBackFolder(): ?string
    {
        $backFolder = $this->dir;

        if (!$backFolder) {
            return null;
        }

        return substr($backFolder, 0, strrpos($backFolder, '/'));
    }

    #[ExposeInTemplate('subFolders')]
    public function getSubFolders(): array
    {
        $finder = new Finder();

        $subFolders = [];
        $finderFolders = $finder->directories()->in($this->realPath())->depth(0);

        $this->sortFinder($finderFolders);

        if ($this->q) {
            $finderFolders->name('/' . $this->q . '/');
        }

        foreach ($finderFolders as $directory) {
            if ($this->isGranted(FileManagerVoter::VIEW, $directory->getPathname())) {
                $subFolders[] = $directory;
            }
        }

        return $subFolders;
    }

    #[ExposeInTemplate('files')]
    public function getFiles(): array
    {
        $finder = new Finder();

        $files = [];
        $finderFiles = $finder->files()->in($this->realPath())->depth(0);

        $this->sortFinder($finderFiles);

        if ($this->q) {
            $finderFiles->name('/' . $this->q . '/');
        }

        foreach ($finderFiles as $file) {
            $files[] = $file;
        }

        return $files;
    }

    #[ExposeInTemplate('editFile')]
    public function getEditFile(): ?array
    {
        if (!$this->currentEditingPath) {
            return null;
        }

        $alt = shell_exec('exiftool -ImageDescription ' . $this->currentEditingPath . ' -s3');

        $this->currentEditingAlt = $alt ?? '';

        return [
            'path' => $this->currentEditingPath,
            'alt' => $alt
        ];
    }

    #[ExposeInTemplate('currentStorage')]
    public function getCurrentStorage(): array
    {
        $total = disk_total_space($this->realPath());
        $free = disk_free_space($this->realPath());

        return [
            'percentage' => round(($total - $free) / $total * 100),
            'total' => $total,
        ];
    }

    #[ExposeInTemplate('tree')]
    public function getTree(): array
    {
        $finder = new Finder();
        $finder->directories()->in($this->getOriginalPath());

        $tree = [
            "/" => []
        ];

        foreach ($finder as $directory) {
            $path = $directory->getPathname();
            $path = str_replace($this->getOriginalPath(), '', $path);
            $path = trim($path, '/');

            $folders = explode('/', $path);

            $current = &$tree["/"];

            foreach ($folders as $folder) {
                if (!isset($current[$folder])) {
                    $current[$folder] = [];
                }

                $current = &$current[$folder];
            }

            unset($current);
        }

        return $tree;
    }

    #[LiveAction]
    public function changeDir(#[LiveArg] string $dir)
    {
        $this->dir = $dir;
    }

    #[LiveAction]
    public function changePath(#[LiveArg] string $path)
    {
        $this->path = $path;
        $this->dir = null;
    }

    #[LiveAction]
    public function createFolder(): void
    {
        $fileSystem = new Filesystem();

        $folder = $this->realPath() . '/' . $this->newFolder;

        if (!$fileSystem->exists($folder)) {
            $fileSystem->mkdir($folder);
        }

        $this->newFolder = "";
    }

    #[LiveAction]
    public function move(#[LiveArg] string $from, #[LiveArg] string $to): void
    {
        $fileSystem = new Filesystem();

        // $to is dir, $from is file
        if (is_dir($to)) {
            $to = $to . '/' . basename($from);
        }

        if (!$fileSystem->exists($to) && is_file($from)) {
            $fileSystem->rename($from, $to);
        } else if (is_file($from)) {
            $fileSystem->remove($from);
        } else if (is_dir($from) && $from !== dirname($to)) {
            $fileSystem->mirror($from, $to);
            $fileSystem->remove($from);
        }
    }

    #[LiveAction]
    public function upload(Request $request, #[LiveArg] ?string $path = null): void
    {
        $files = $request->files->all('upload');
        $pathTo = $path ?? $this->realPath();

        foreach ($files as $file) {
            try {
                $file->move($pathTo, $file->getClientOriginalName());

                // optimize image with imagick
                shell_exec('convert ' . $pathTo . '/' . $file->getClientOriginalName() . ' -resize 1920x1080 ' . $pathTo . '/' . $file->getClientOriginalName());
            } catch (\Exception $e) {
                dd($e->getMessage(), $pathTo);
            }
        }
    }

    #[LiveAction]
    public function delete(#[LiveArg] string $path): void
    {
        $fileSystem = new Filesystem();

        if ($fileSystem->exists($path)) {
            $fileSystem->remove($path);
        }
    }

    #[LiveAction]
    public function edit(#[LiveArg] string $path): void
    {
        $this->currentEditingPath = $path;
    }

    #[LiveAction]
    public function changeView(#[LiveArg] string $view): void
    {
        $this->view = $view;
    }

    #[LiveAction]
    public function saveEdit(): void
    {
        shell_exec('exiftool -overwrite_original -ImageDescription="' . $this->currentEditingAlt . '" ' . $this->currentEditingPath . ' -s3');
    }

    #[LiveAction]
    public function choose(#[LiveArg] string $path, Request $request): void
    {
        $session = $request->getSession();

        $config = $this->fileManagerExtensionRuntime->getConfig()['paths'][$this->path] ?? null;
        if (!$config) {
            throw $this->createNotFoundException();
        }

        dd($path);

        $fullPath = $config['path'] . $path;

        // keep only 5 recently used files
        $recentlyUsed = $session->get(self::RECENTLY_USED_TOKEN, []);
        $recentlyUsed = array_slice(array_unique(array_merge([$fullPath], $recentlyUsed)), 0, 5);
        $session->set(self::RECENTLY_USED_TOKEN, $recentlyUsed);

        $this->dispatchBrowserEvent('filemanager:choose', [
            'path' => $fullPath,
            'inputId' => $this->inputId,
            'preview' => $this->generateUrl('ux.file_manager.render', ['path' => '/'.$path, 'configurationKey' => $this->path])
        ]);
    }

    #[LiveAction]
    public function orderByToggle(#[LiveArg] string $by): void
    {
        if ($this->orderBy === $by) {
            $this->orderByDirection = $this->orderByDirection === self::ORDER_BY_ASC ? self::ORDER_BY_DESC : self::ORDER_BY_ASC;
        } else {
            $this->orderByDirection = self::ORDER_BY_ASC;
        }

        $this->orderBy = $by;
    }

    #[LiveAction]
    public function bulk()
    {
        $fileSystem = new Filesystem();

        switch ($this->bulkAction) {
            case 'delete':
                foreach ($this->selectedPaths as $path) {
                    if ($fileSystem->exists($path)) {
                        $fileSystem->remove($path);
                    }
                }
                break;
            case 'zip':
                $zip = new \ZipArchive();
                $zipPath = $this->realPath() . '/archive_' . date('Y-m-d_H-i-s') . '.zip';

                if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {

                    // check if is a file or a folder
                    foreach ($this->selectedPaths as $path) {
                        if (is_dir($path)) {
                            $files = $this->recursiveDirectoryIterator($path);

                            foreach ($files as $file) {
                                $path = str_replace($this->realPath(), '', $file);

                                if (is_dir($file)) {
                                    $zip->addEmptyDir($path);
                                } else {
                                    $zip->addFile($file, $path);
                                }
                            }
                        } else {
                            $zip->addFile($path, basename($path));
                        }
                    }

                    $zip->close();
                }

                break;
        }

        $this->selectedPaths = [];
    }

    private function recursiveDirectoryIterator(string $path): array
    {
        $files = [];
        $directory = new \RecursiveDirectoryIterator($path);
        $iterator = new \RecursiveIteratorIterator($directory);

        foreach ($iterator as $info) {
            $files[] = $info->getPathname();
        }

        return $files;
    }

    private function sortFinder(Finder $finder): void
    {
        switch ($this->orderBy) {
            case self::ORDER_BY_NAME:
                $finder->sortByName();
                break;
            case self::ORDER_BY_SIZE:
                $finder->sortBySize();
                break;
            case self::ORDER_BY_MTIME:
                $finder->sortByModifiedTime();
                break;
        }

        if ($this->orderByDirection === self::ORDER_BY_DESC) {
            $finder->reverseSorting();
        }
    }
}
