<?php

// src/AppBundle/Entity/Category.php

namespace App\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\MediaBundle\Entity\Media;

#[ORM\Entity(repositoryClass: 'App\AppBundle\Repository\CategoryRepository')]
#[ORM\Table(name: 'category_table')]
#[UniqueEntity(fields: ['title'])]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;  // Allowing the id to be nullable

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 25)]
    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\ManyToMany(targetEntity: 'App\AppBundle\Entity\Status', mappedBy: 'categories')]
    #[ORM\OrderBy(['created' => 'DESC'])]
    private $status;

    #[Assert\File(mimeTypes: ['image/jpeg', 'image/png'], maxSize: '40M')]
    private $file;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', nullable: false)]
    private Media $media;

    public function __construct()
    {
        $this->status = new ArrayCollection();
        $this->position = 0; // Default initialization
    }

    public function getId(): int
    {
        return $this->id ?? 0;  // Return 0 if $id is null
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

    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function addStatus(Status $status): self
    {
        $this->status[] = $status;
        return $this;
    }

    public function removeStatus(Status $status): void
    {
        $this->status->removeElement($status);
    }

    public function getStatus(): \Doctrine\Common\Collections\Collection
    {
        return $this->status;
    }

    public function __toString(): string
    {
        return $this->title;
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

    public function getCategory()
    {
        return $this;
    }
}
