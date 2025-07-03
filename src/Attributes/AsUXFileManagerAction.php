<?php

namespace Akyos\UXFileManager\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class AsUXFileManagerAction
{
    public function __construct(
        public string $label,
        public string $icon,
    ) {}

    public function serviceConfig(): array
    {
        return [
            'label' => $this->label,
            'icon' => $this->icon,
        ];
    }
}