<?php

namespace Akyos\UXFileManager\Security\Voter;

use Akyos\UXFileManager\Twig\FileManagerExtensionRuntime;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class FileManagerVoter extends Voter
{
    public const VIEW = 'UX.FILE_MANAGER.VIEW';

    public function __construct(
        private Security $security,
        private FileManagerExtensionRuntime $fileManagerExtensionRuntime
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::VIEW])
            && $subject;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return match ($attribute) {
            self::VIEW => $this->canView($subject),
            default => false,
        };
    }

    private function canView(string $path)
    {
        $configuration = $this->fileManagerExtensionRuntime->getConfiguration($path);

        if ($configuration['security'] === null) {
            return true;
        }

        return $this->security->isGranted($configuration['security'], $path);
    }
}
