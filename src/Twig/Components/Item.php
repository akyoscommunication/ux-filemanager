<?php

namespace Akyos\UXFileManager\Twig\Components;

use Akyos\UXFileManager\Enum\Views;
use Akyos\UXFileManager\Twig\FileManagerExtensionRuntime;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

#[AsTwigComponent('UX:FileManager:item')]
#[AsEventListener(PreRenderEvent::class, 'onPreRender')]
final class Item
{
    public Views $view;
    public ?string $dir;
    public string $storage;
    public SplFileInfo|string $item;

    public function onPreRender(PreRenderEvent $event): void
    {
        $component = $event->getComponent();
        if ($component::class !== $this::class) {
            return;
        }

        $dir = $component->dir ?? '/.';

        $type = 'file';
        if (is_string($component->item)) {
            $type = 'back';
            $path = $component->item;
        } elseif ($component->item->isDir()) {
            $type = 'folder';
            $path = $dir . '/' . $component->item->getFilename();
        } else {
            $path = $dir . '/' . $component->item->getFilename();
        }

        $view = $component->view->value;

        $event->setTemplate("@UXFileManager/twig_components/item/$type/$view.html.twig");
        $event->setVariables(array_merge($event->getVariables(), [
            'path' => $path,
        ]));
    }
}
