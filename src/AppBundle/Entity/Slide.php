<?php

// src/AppBundle/Entity/Slide.php

namespace App\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\MediaBundle\Entity\Media;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: 'App\AppBundle\Repository\SlideRepository')]
#[ORM\Table(name: 'slide_table')]
class Slide
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $url;

    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    #[ORM\ManyToOne(targetEntity: 'App\AppBundle\Entity\Status')]
    #[ORM\JoinColumn(name: 'status_id', referencedColumnName: 'id', nullable: true)]
    private ?Status $status;

    #[ORM\ManyToOne(targetEntity: 'App\AppBundle\Entity\Category')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true)]
    private ?Category $category;

    #[ORM\ManyToOne(targetEntity: 'App\MediaBundle\Entity\Media')]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', nullable: false)]
    private Media $media;

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 1, max: 10000)]
    private int $position;

    #[Assert\File(mimeTypes: ['image/jpeg', 'image/png'], maxSize: '40M')]
    private ?File $file = null; // Set the default value to null

    public function __construct()
    {
        // Initialize file as null to avoid uninitialized property issues
        $this->file = null;
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setStatus(?Status $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
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

    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }
}
