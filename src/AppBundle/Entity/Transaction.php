<?php

// src/AppBundle/Entity/Transaction.php

namespace App\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\MediaBundle\Entity\Media;
use App\UserBundle\Entity\User;

#[ORM\Entity(repositoryClass: 'App\AppBundle\Repository\TransactionRepository')]
#[ORM\Table(name: 'transaction_table')]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;  // Allowing the id to be nullable

    #[ORM\Column(type: 'string', length: 255)]
    private string $label;

    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    #[ORM\Column(type: 'integer')]
    private int $points;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'invited_id', referencedColumnName: 'id', nullable: false)]
    private User $invited;

    #[ORM\ManyToOne(targetEntity: 'App\AppBundle\Entity\Status', inversedBy: 'transaction')]
    #[ORM\JoinColumn(name: 'status_id', referencedColumnName: 'id', nullable: true)]
    private $status;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $created;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled;

    public function __construct()
    {
        $this->created = new \DateTime();
        $this->enabled = true;
    }

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;
        return $this;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        $this->updateLabelBasedOnType();
        return $this;
    }

    private function updateLabelBasedOnType(): void
    {
        switch ($this->getType()) {
            case 'view_quote':
                $this->label = "You see the status: " . $this->status->getDescription();
                break;
            case 'share_quote':
                $this->label = "You share the status: " . $this->status->getDescription();
                break;
            case 'add_quote':
                $this->label = "You add new status: " . $this->status->getDescription();
                break;
            case 'view_video':
                $this->label = "You watch the video: " . $this->status->getTitle();
                break;
            case 'share_video':
                $this->label = "You share the video: " . $this->status->getTitle();
                break;
            case 'add_video':
                $this->label = "You upload new video: " . $this->status->getTitle();
                break;
            case 'view_image':
                $this->label = "You see the image: " . $this->status->getTitle();
                break;
            case 'share_image':
                $this->label = "You share the image: " . $this->status->getTitle();
                break;
            case 'add_image':
                $this->label = "You upload new image: " . $this->status->getTitle();
                break;
            case 'view_gif':
                $this->label = "You see the Gif: " . $this->status->getTitle();
                break;
            case 'share_gif':
                $this->label = "You share the Gif: " . $this->status->getTitle();
                break;
            case 'add_gif':
                $this->label = "You upload new Gif: " . $this->status->getTitle();
                break;
            case 'invited_user':
                $this->label = "You invite the user: " . $this->invited->getName();
                break;
        }
    }

    public function getInvited(): User
    {
        return $this->invited;
    }

    public function setInvited(User $invited): self
    {
        $this->invited = $invited;
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
}
