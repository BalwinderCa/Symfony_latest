<?php

// src/AppBundle/Entity/Withdraw.php

namespace App\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\UserBundle\Entity\User;

#[ORM\Entity(repositoryClass: 'App\AppBundle\Repository\WithdrawRepository')]
#[ORM\Table(name: 'withdraws_table')]
class Withdraw
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;  // Allowing the id to be nullable

    #[ORM\Column(type: 'string', length: 255)]
    private string $methode;

    #[ORM\Column(type: 'string', length: 255)]
    private string $account;

    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    #[ORM\Column(type: 'integer')]
    private int $points;

    #[ORM\Column(type: 'string', length: 255)]
    private string $amount;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $user;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $created;

    public function __construct()
    {
        $this->created = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;
        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
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

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setMethode(string $methode): self
    {
        $this->methode = $methode;
        return $this;
    }

    public function getMethode(): string
    {
        return $this->methode;
    }

    public function setAccount(string $account): self
    {
        $this->account = $account;
        return $this;
    }

    public function getAccount(): string
    {
        return $this->account;
    }
}
