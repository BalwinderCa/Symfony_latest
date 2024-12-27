<?php

// src/AppBundle/Entity/Language.php

namespace App\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\MediaBundle\Entity\Media;

#[ORM\Entity(repositoryClass: 'App\AppBundle\Repository\LanguageRepository')]
#[ORM\Table(name: 'language_table')]
class Language
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;  // Allowing the id to be nullable

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 25)]
    private string $language;

    #[ORM\Column(type: 'integer')]
    private int $position;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled;

    #[Assert\File(mimeTypes: ['image/jpeg', 'image/png'], maxSize: '40M')]
    private $file;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', nullable: false)]
    private Media $media;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file): self
    {
        $this->file = $file;
        return $this;
    }

    public function setMedia(Media $media): self
    {
        $this->media = $media;
        return $this;
    }

    public function getMedia(): Media
    {
        return $this->media;
    }

    public function __toString(): string
    {
        return $this->language;
    }
}
