<?php

namespace Akyos\UXFileManager\Twig\Components;

use Akyos\UXFileManager\Entity\File;
use Akyos\UXFileManager\Enum\Views;
use Akyos\UXFileManager\Repository\FileRepository;
use Akyos\UXFileManager\Security\Voter\FileManagerVoter;
use Akyos\UXFileManager\Twig\FileManagerExtensionRuntime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * @rules :
 *  - folder must not end with / and must begin with /
 *  - path of file must begin with /
 */
#[AsLiveComponent('UX:FileManager', template: '@UXFileManager/twig_components/UXFileManager.html.twig')]
final class UXFileManager extends AbstractController
{
    use DefaultActionTrait, ComponentToolsTrait;

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
    public Views $view = Views::LIST;

    #[LiveProp]
    public ?string $inputId = null;

    #[LiveProp(writable: true)]
    public ?string $orderBy = null;

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
        private readonly FileRepository $fileRepository,
        private readonly TranslatorInterface $translator
    ) {}

    private function getOriginalPath(): string
    {
        return $this->fileManagerExtensionRuntime->getConfig()['paths'][$this->path]['path'];
    }

    #[ExposeInTemplate(self::RECENTLY_USED_TOKEN)]
    public function getRecentlyUsed(): array
    {
        return array_filter(
            array_map(fn($id) => $this->fileRepository->find($id), $this->requestStack->getSession()->get(self::RECENTLY_USED_TOKEN, [])),
            fn(?File $file) => $file && $this->isGranted(FileManagerVoter::VIEW, $file->getPath()) && file_exists($file->getPath()),
        );
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

    private function realPath(): string
    {
        return $this->getOriginalPath() . $this->dir;
    }

    #[ExposeInTemplate('is_granted')]
    public function isGrantedToView(): bool
    {
        return $this->isGranted(FileManagerVoter::VIEW, $this->realPath());
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

    #[ExposeInTemplate('items')]
    public function getItems(): array
    {
        $finder = new Finder();

        $items = [];
        $finderItems = $finder->in($this->realPath())->depth(0);

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
            default:
                // put directories first
                $finder->sort(function (\SplFileInfo $a, \SplFileInfo $b) {
                    if ($a->isDir() && !$b->isDir()) {
                        return -1;
                    }

                    if (!$a->isDir() && $b->isDir()) {
                        return 1;
                    }

                    return $a->getFilename() <=> $b->getFilename();
                });
        }

        if ($this->orderByDirection === self::ORDER_BY_DESC) {
            $finder->reverseSorting();
        }

        if ($this->q) {
            $finderItems->name('/' . $this->q . '/');
        }

        foreach ($finderItems as $item) {
            if ($this->isGranted(FileManagerVoter::VIEW, $item->getPathname())) {
                $items[] = $item;
            }
        }

        if ($this->getBackFolder() !== null) {
            // set to first element
            array_unshift($items, $this->getBackFolder());
        }

        return $items;
    }

    #[ExposeInTemplate('editFile')]
    public function getEditFile(): ?array
    {
        if (!$this->currentEditingPath) {
            return null;
        }

        $file = $this->fileManagerExtensionRuntime->getFile($this->getOriginalPath() . $this->currentEditingPath);

        $this->currentEditingAlt = $file->getAlt() ?? '';

        return [
            'path' => $file->getPath(),
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
            '' => [
                'name' => $this->translator->trans('root', domain: 'ux_filemanager'),
                'children' => []
            ]
        ];

        foreach ($finder as $directory) {
            $path = $directory->getPathname();
            $path = str_replace($this->getOriginalPath(), '', $path);
            $path = trim($path, '/');

            $folders = explode('/', $path);

            $current = &$tree[""];

            foreach ($folders as $folder) {
                if (!isset($current['children'][$folder])) {
                    $current['children'][$folder] = [
                        'name' => $folder,
                        'children' => []
                    ];
                }

                $current = &$current['children'][$folder];
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
        $from = $this->getOriginalPath() . $from;
        $to = $this->getOriginalPath() . $to;
        $isDirFrom = is_dir($from);

        // check if $to is not in $from
        if (strpos($to, $from) === 0) {
            return;
        }

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

        if ($isDirFrom) {
            $this->fileManagerExtensionRuntime->managePathInDirectory($from, $to);
        } else {
            $this->fileManagerExtensionRuntime->managePath($from, $to);
        }
    }

    #[LiveAction]
    public function upload(Request $request, #[LiveArg] ?string $path = null): void
    {
        $files = $request->files->all('upload');
        $pathTo = $this->realPath() . $path;

        foreach ($files as $file) {
            try {
                $file->move($pathTo, $file->getClientOriginalName());
                $to = $pathTo . '/' . $file->getClientOriginalName();

                // optimize image with imagick
                shell_exec('convert ' . $to . ' -resize 1920x1080 ' . $to);

                $this->fileManagerExtensionRuntime->managePath($to);
            } catch (\Exception $e) {
                dd($e->getMessage(), $pathTo);
            }
        }
    }

    #[LiveAction]
    public function delete(#[LiveArg] string $path): void
    {
        $fileSystem = new Filesystem();
        $path = $this->getOriginalPath() . $path;

        if ($fileSystem->exists($path)) {
            $fileSystem->remove($path);

            $this->fileManagerExtensionRuntime->deleteFile($path);
        }
    }

    #[LiveAction]
    public function edit(#[LiveArg] string $path): void
    {
        $this->currentEditingPath = $path;
    }

    #[LiveAction]
    public function changeView(#[LiveArg] Views $view): void
    {
        $this->view = $view;
    }

    #[LiveAction]
    public function saveEdit(): void
    {
        $this->fileManagerExtensionRuntime->setAlt($this->getOriginalPath() . $this->currentEditingPath, $this->currentEditingAlt);
    }

    #[LiveAction]
    public function choose(#[LiveArg] string $path, Request $request): void
    {
        $session = $request->getSession();

        $config = $this->fileManagerExtensionRuntime->getConfig()['paths'][$this->path] ?? null;
        if (!$config) {
            throw $this->createNotFoundException();
        }

        // remove / at the end of the path if exists
        $pathOfDir = rtrim($config['path'], '/');
        $fullPath = $pathOfDir . $path;
        $file = $this->fileManagerExtensionRuntime->getFile($fullPath, true);
        $id = $file->getId();

        // keep only 5 recently used files
        $recentlyUsed = $session->get(self::RECENTLY_USED_TOKEN, []);
        $recentlyUsed = array_slice(array_unique(array_merge([$id], $recentlyUsed)), 0, 5);
        $session->set(self::RECENTLY_USED_TOKEN, $recentlyUsed);

        $this->fileManagerExtensionRuntime->managePath($fullPath);

        $this->dispatchBrowserEvent('filemanager:choose', [
            'path' => $fullPath,
            'id' => $id,
            'inputId' => $this->inputId,
            'name' => basename($fullPath),
            'preview' => $this->generateUrl('ux.file_manager.render', ['path' => '/'.$path, 'configurationKey' => $this->path]),
            'mimeType' => mime_content_type($fullPath)
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
}
