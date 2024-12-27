<?php

// src/AppBundle/Entity/Device.php

namespace App\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\AppBundle\Repository\DeviceRepository')]
#[ORM\Table(name: 'device_table')]
class Device
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;  // Allowing the id to be nullable

    #[ORM\Column(type: 'text')]
    private string $token;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
