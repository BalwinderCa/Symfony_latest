<?php

namespace App\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\MediaBundle\Entity\Media as Media;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Table(name: 'fos_user_table')]
#[ORM\Entity(repositoryClass: 'App\UserBundle\Repository\UserRepository')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    protected ?string $name = null;

    #[ORM\Column(name: 'code', type: 'string', length: 255, nullable: true)]
    protected ?string $code = null;

    #[ORM\Column(name: 'facebook', type: 'string', length: 255, nullable: true)]
    protected ?string $facebook = null;

    #[ORM\Column(name: 'instagram', type: 'string', length: 255, nullable: true)]
    protected ?string $instagram = null;

    #[ORM\Column(name: 'twitter', type: 'string', length: 255, nullable: true)]
    protected ?string $twitter = null;

    #[ORM\Column(name: 'emailo', type: 'string', length: 255, nullable: true)]
    protected ?string $emailo = null;

    #[ORM\Column(name: 'type', type: 'string', length: 255, nullable: true)]
    protected ?string $type = null;

    #[ORM\Column(name: 'token', type: 'text', nullable: true)]
    protected ?string $token = null;

    #[ORM\Column(name: 'image', type: 'text')]
    private ?string $image = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(name: 'password_reset_token', type: 'string', length: 255, nullable: true)]
    private ?string $passwordResetToken = null;


    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\ManyToMany(targetEntity: 'User')]
    #[ORM\JoinTable(name: 'user_followers',
        joinColumns: [new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'follower_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    )]
    private $followers;

    #[ORM\OneToMany(targetEntity: 'App\AppBundle\Entity\Status', mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['created' => 'ASC'])]
    private $status;

    #[ORM\ManyToMany(targetEntity: 'User', mappedBy: 'followers')]
    private $users;

    #[ORM\Column(name: 'trusted', type: 'boolean')]
    private bool $trusted;

    public function __construct()
    {
        $this->followers = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->status = new ArrayCollection();
        $this->roles = $this->roles ?? [];
        $this->trusted = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }
    
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getName()
    {
        return ucfirst($this->name);
    }
    
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

     /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function __toString()
    {
       return $this->getName();
    }

    public function getImage()
    {
        return $this->image;
    }
    
    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    public function addFollower(User $follower)
    {
        $this->followers[] = $follower;
        return $this;
    }

    public function removeFollower(User $follower)
    {
        $this->followers->removeElement($follower);
    }

    public function getFollowers()
    {
        return $this->followers;
    }

    public function setFollowers($followers)
    {
        return $this->followers = $followers;
    }

    public function addUser(User $user)
    {
        $this->users[] = $user;
        return $this;
    }

    public function removeUser(User $user)
    {
        $this->users->removeElement($user);
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function setUsers($users)
    {
        return $this->users = $users;
    }

    public function getFacebook()
    {
        return $this->facebook;
    }
    
    public function setFacebook($facebook)
    {
        $this->facebook = $facebook;
        return $this;
    }

    public function getTwitter()
    {
        return $this->twitter;
    }
    
    public function setTwitter($twitter)
    {
        $this->twitter = $twitter;
        return $this;
    }

    public function getInstagram()
    {
        return $this->instagram;
    }
    
    public function setInstagram($instagram)
    {
        $this->instagram = $instagram;
        return $this;
    }

    public function getEmailo()
    {
        return $this->emailo;
    }
    
    public function setEmailo($emailo)
    {
        $this->emailo = $emailo;
        return $this;
    }

    public function getToken()
    {
        return $this->token;
    }
    
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getCode()
    {
        return $this->code;
    }
    
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function getTrusted()
    {
        return $this->trusted;
    }
    
    public function setTrusted($trusted)
    {
        $this->trusted = $trusted;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setPasswordResetToken(?string $passwordResetToken): self
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }
}