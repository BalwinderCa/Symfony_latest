<?php

namespace App\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;

 #[ORM\Entity(repositoryClass: 'App\AppBundle\Repository\MediaRepository')]
 #[ORM\Table(name: 'media_table')]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\File(mimeTypes: ["image/gif", "image/jpeg", "image/png"], maxSize: "10M")]
    private ?File $file = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $url;

    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $extension;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled;

    private ?string $fileName = null;

    public function __construct()
    {
        $this->date = new \DateTimeImmutable();
        $this->enabled = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(File $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function upload(string $path): void
    {
        $file = $this->getFile();
        $this->fileName = md5(uniqid()) . '.' . $file->guessExtension();
        $this->setUrl($this->fileName);
        $this->setType($file->getMimeType());
        $this->setExtension($file->guessExtension());
        $file->move($path . '/' . $file->guessExtension(),$this->fileName);
    }

    public function getLink(): string
    {
        return $this->enabled ? "uploads/{$this->extension}/{$this->url}" : $this->url;
    }

    public function delete(string $url): void
    {
        if ($this->getEnabled()) {
            @unlink($url . $this->getExtension() . "/" . $this->getUrl());
        }
    }

    public function addVideo(string $url): void
    {
        $videoId = explode("?v=", $url);
        if (empty($videoId[1])) {
            $videoId = explode("/v/", $url);
        }
        $videoId = explode("&", $videoId[1])[0];
        $content = file_get_contents("http://youtube.com/get_video_info?video_id=" . $videoId);
        parse_str($content, $ytarr);

        if ($ytarr['title'] !== null) {
            $this->setTitre($ytarr['title']);
        }
        $this->setUrl($url);
        $this->setType("youtube");
    }

    public function getImage(): string
    {
        try {
            $videoId = explode("?v=", $this->url);
            if (empty($videoId[1])) {
                $videoId = explode("/v/", $this->url);
            }
            $videoId = explode("&", $videoId[1])[0];
        } catch (\Exception $e) {
            $videoId = "invalid";
        }

        return "http://img.youtube.com/vi/{$videoId}/hqdefault.jpg";
    }

    public function getImageL(): string
    {
        try {
            $videoId = explode("?v=", $this->url);
            if (empty($videoId[1])) {
                $videoId = explode("/v/", $this->url);
            }
            $videoId = explode("&", $videoId[1])[0];
        } catch (\Exception $e) {
            $videoId = "invalid";
        }

        return "http://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";
    }

    public function generateThum(string $path): self
    {
        $thum = new self();
        $thum->setExtension("png");
        $thum->setType("image/png");
        $thum->setTitre(str_replace(".gif", "", $this->getTitre()));
        $thum->setUrl($this->fileName . "." . $thum->getExtension());
        $thum->setEnabled(true);

        imagepng(imagecreatefromstring(file_get_contents($this->getLink())), $path . "/" . $thum->getExtension() . "/" . $this->fileName . "." . $thum->getExtension());

        return $thum;
    }
}
