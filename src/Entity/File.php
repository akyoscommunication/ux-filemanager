<?php

namespace Akyos\UXFileManager\Entity;

use Akyos\UXFileManager\Enum\Mimes;
use App\Repository\FileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FileRepository::class)]
class File
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 510)]
    private ?string $path = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $alt = null;

    #[ORM\Column(enumType: Mimes::class)]
    private ?Mimes $mime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): static
    {
        $this->alt = $alt;

        return $this;
    }

    public function getMime(): ?Mimes
    {
        return $this->mime;
    }

    public function setMime(Mimes $mime): static
    {
        $this->mime = $mime;

        return $this;
    }
}
