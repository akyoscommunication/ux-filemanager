<?php

namespace Akyos\UXFileManager\Attributes;

use Attribute;

/**
 * Attribut pour définir une action personnalisée dans le gestionnaire de fichiers UX.
 * 
 * Cet attribut doit être appliqué à une méthode qui accepte un seul paramètre
 * de type Akyos\UXFileManager\Entity ou Directory.
 * 
 * @param string $label Le libellé affiché pour l'action
 * @param string $icon L'icône à afficher pour l'action
 * 
 * @example
 * #[AsUXFileManagerAction(label: 'Télécharger', icon: 'download')]
 * public function downloadAction(Akyos\UXFileManager\Entity $file): void
 * {
 *     // Logique de téléchargement
 * }
 */
#[Attribute(Attribute::TARGET_METHOD)]
class AsUXFileManagerAction
{
    /**
     * @param string $label Le libellé affiché pour l'action
     * @param string $icon L'icône à afficher pour l'action
     */
    public function __construct(
        public string $label,
        public string $icon,
    ) {}

    /**
     * @return array{label: string, icon: string}
     */
    public function serviceConfig(): array
    {
        return [
            'label' => $this->label,
            'icon' => $this->icon,
        ];
    }
}