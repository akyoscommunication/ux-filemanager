<?php

namespace Akyos\UXFileManager\Controller;

use Akyos\UXFileManager\Enum\Mimes;
use Akyos\UXFileManager\Security\Voter\FileManagerVoter;
use Akyos\UXFileManager\Twig\FileManagerExtensionRuntime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/_ux-file-manager', name: 'ux.file_manager.')]
final class FileManagerController extends AbstractController
{
    #[Route('/render', name: 'render')]
    public function streamFile(
        #[MapQueryParameter] string $path,
        #[MapQueryParameter] string $configurationKey,
        FileManagerExtensionRuntime $fileManagerExtensionRuntime
    ): Response
    {
        $configuration = $fileManagerExtensionRuntime->getConfig()['paths'][$configurationKey] ?? null;

        if (!$configuration) {
            throw $this->createNotFoundException();
        }

        $pathToFile = $configuration['path'] . $path;

        $security = $configuration['security'];

        if ($security && !$this->isGranted(FileManagerVoter::VIEW, $pathToFile)) {
            throw $this->createAccessDeniedException();
        }

        $mime =  Mimes::from(mime_content_type($pathToFile));

        if (Mimes::isRenderIco($mime)) {
            return $this->render('@UXFileManager/render.html.twig', ['mime' => $mime]);
        }

        return new BinaryFileResponse($pathToFile);
    }
}
