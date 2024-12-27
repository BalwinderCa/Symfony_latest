<?php

// src/AppBundle/Entity/Settings.php

namespace App\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\AppBundle\Repository\SettingsRepository')]
#[ORM\Table(name: 'settings_table')]
class Settings
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;  // Allowing the id to be nullable

    #[ORM\Column(type: 'text')]
    private string $firebasekey;

    #[ORM\Column(type: 'integer')]
    private int $sharevideo;

    #[ORM\Column(type: 'integer')]
    private int $viewvideo;

    #[ORM\Column(type: 'integer')]
    private int $addvideo;

    #[ORM\Column(type: 'integer')]
    private int $shareimage;

    #[ORM\Column(type: 'integer')]
    private int $viewimage;

    #[ORM\Column(type: 'integer')]
    private int $addimage;

    #[ORM\Column(type: 'integer')]
    private int $sharegif;

    #[ORM\Column(type: 'integer')]
    private int $viewgif;

    #[ORM\Column(type: 'integer')]
    private int $addgif;

    #[ORM\Column(type: 'integer')]
    private int $sharequote;

    #[ORM\Column(type: 'integer')]
    private int $viewquote;

    #[ORM\Column(type: 'integer')]
    private int $addquote;

    #[ORM\Column(type: 'integer')]
    private int $adduser;

    #[ORM\Column(type: 'integer')]
    private int $minpoints;

    #[ORM\Column(type: 'string', length: 255)]
    private string $currency;

    #[ORM\Column(type: 'integer')]
    private int $oneusdtopoints;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getFirebasekey(): string
    {
        return $this->firebasekey;
    }

    public function setFirebasekey(string $firebasekey): self
    {
        $this->firebasekey = $firebasekey;
        return $this;
    }

    public function getSharevideo(): int
    {
        return $this->sharevideo;
    }

    public function setSharevideo(int $sharevideo): self
    {
        $this->sharevideo = $sharevideo;
        return $this;
    }

    public function getViewvideo(): int
    {
        return $this->viewvideo;
    }

    public function setViewvideo(int $viewvideo): self
    {
        $this->viewvideo = $viewvideo;
        return $this;
    }

    public function getAddvideo(): int
    {
        return $this->addvideo;
    }

    public function setAddvideo(int $addvideo): self
    {
        $this->addvideo = $addvideo;
        return $this;
    }

    public function getShareimage(): int
    {
        return $this->shareimage;
    }

    public function setShareimage(int $shareimage): self
    {
        $this->shareimage = $shareimage;
        return $this;
    }

    public function getViewimage(): int
    {
        return $this->viewimage;
    }

    public function setViewimage(int $viewimage): self
    {
        $this->viewimage = $viewimage;
        return $this;
    }

    public function getAddimage(): int
    {
        return $this->addimage;
    }

    public function setAddimage(int $addimage): self
    {
        $this->addimage = $addimage;
        return $this;
    }

    public function getSharegif(): int
    {
        return $this->sharegif;
    }

    public function setSharegif(int $sharegif): self
    {
        $this->sharegif = $sharegif;
        return $this;
    }

    public function getAddgif(): int
    {
        return $this->addgif;
    }

    public function setAddgif(int $addgif): self
    {
        $this->addgif = $addgif;
        return $this;
    }

    public function getSharequote(): int
    {
        return $this->sharequote;
    }

    public function setSharequote(int $sharequote): self
    {
        $this->sharequote = $sharequote;
        return $this;
    }

    public function getViewquote(): int
    {
        return $this->viewquote;
    }
    
    public function getViewgif()
    {
        return $this->viewgif;
    }
    
    public function setViewgif($viewgif)
    {
        $this->viewgif = $viewgif;
        return $this;
    }
    public function setViewquote(int $viewquote): self
    {
        $this->viewquote = $viewquote;
        return $this;
    }

    public function getAddquote(): int
    {
        return $this->addquote;
    }

    public function setAddquote(int $addquote): self
    {
        $this->addquote = $addquote;
        return $this;
    }

    public function getAdduser(): int
    {
        return $this->adduser;
    }

    public function setAdduser(int $adduser): self
    {
        $this->adduser = $adduser;
        return $this;
    }

    public function getMinpoints(): int
    {
        return $this->minpoints;
    }

    public function setMinpoints(int $minpoints): self
    {
        $this->minpoints = $minpoints;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getOneusdtopoints(): int
    {
        return $this->oneusdtopoints;
    }

    public function setOneusdtopoints(int $oneusdtopoints): self
    {
        $this->oneusdtopoints = $oneusdtopoints;
        return $this;
    }

    public function getPoints(string $name): int
    {
        return $this->$name;
    }
}

