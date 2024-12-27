<?php
// src/Entity/Status.php
namespace App\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\MediaBundle\Entity\Media;
use App\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection; // Import the Collection interface

#[ORM\Entity(repositoryClass: 'App\AppBundle\Repository\StatusRepository')]
#[ORM\Table(name: 'status_table')]
class Status
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;  // Allowing the id to be nullable

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: 'integer')]
    private int $font;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private int $downloads;

    #[ORM\Column(type: 'integer')]
    private int $views;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $created;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id')]
    private Media $media;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $user;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled;

    #[ORM\Column(type: 'boolean')]
    private bool $review;

    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(name: 'status_categories')]
    private Collection $categories;

    #[ORM\ManyToMany(targetEntity: Language::class)]
    #[ORM\JoinTable(name: 'status_languages')]
    private Collection $languages;

    #[Assert\File(mimeTypes: ['image/jpeg', 'image/png'], maxSize: '200M')]
    private ?\Symfony\Component\HttpFoundation\File\UploadedFile $file = null;

    #[Assert\File(mimeTypes: ['image/gif'], maxSize: '200M')]
    private ?\Symfony\Component\HttpFoundation\File\UploadedFile $filegif = null;

    #[Assert\File(mimeTypes: ['video/mp4'], maxSize: '200M')]
    private ?\Symfony\Component\HttpFoundation\File\UploadedFile $filevideo = null;

    #[Assert\Url]
    #[Assert\Length(min: 3)]
    private ?string $urlvideo = null;

    #[ORM\OneToMany(mappedBy: 'status', targetEntity: \App\AppBundle\Entity\Comment::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['created' => 'desc'])]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'status', targetEntity: \App\AppBundle\Entity\Transaction::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['created' => 'desc'])]
    private Collection $transactions;

    #[ORM\Column(type: 'boolean')]
    private bool $comment;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $tags = null;

    #[ORM\Column(type: 'integer')]
    private int $angry;

    #[ORM\Column(type: 'integer')]
    private int $haha;

    #[ORM\Column(name: '`like`', type: 'integer')]
    private int $like;

    #[ORM\Column(type: 'integer')]
    private int $love;

    #[ORM\Column(type: 'integer')]
    private int $sad;

    #[ORM\Column(type: 'integer')]
    private int $woow;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(name: 'video_id', referencedColumnName: 'id')]
    private Media $video;

    public function __construct()
    {
        $this->font = 1;
        $this->like = 0;
        $this->love = 0;
        $this->angry = 0;
        $this->sad = 0;
        $this->woow = 0;
        $this->haha = 0;
        $this->views = 0;
        $this->categories = new ArrayCollection();
        $this->languages = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->created = new \DateTime();
        $this->review = false;
    }

    public function getId(): ?int
    {
        return $this->id ?? 0;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDownloads(): int
    {
        return $this->downloads;
    }

    public function setDownloads(int $downloads): self
    {
        $this->downloads = $downloads;
        return $this;
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

    public function getMedia(): Media
    {
        return $this->media;
    }

    public function setMedia(Media $media): self
    {
        $this->media = $media;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
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

    public function getReview(): bool
    {
        return $this->review;
    }

    public function setReview(bool $review): self
    {
        $this->review = $review;
        return $this;
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
        }
        return $this;
    }

    public function removeCategory(Category $category): self
    {
        $this->categories->removeElement($category);
        return $this;
    }

    public function getLanguages(): Collection
    {
        return $this->languages;
    }

    public function addLanguage(Language $language): self
    {
        if (!$this->languages->contains($language)) {
            $this->languages[] = $language;
        }
        return $this;
    }

    public function removeLanguage(Language $language): self
    {
        $this->languages->removeElement($language);
        return $this;
    }

    public function getFile(): ?\Symfony\Component\HttpFoundation\File\UploadedFile
    {
        return $this->file;
    }

    public function setFile(?\Symfony\Component\HttpFoundation\File\UploadedFile $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function getFilegif(): ?\Symfony\Component\HttpFoundation\File\UploadedFile
    {
        return $this->filegif;
    }

    public function setFilegif(?\Symfony\Component\HttpFoundation\File\UploadedFile $filegif): self
    {
        $this->filegif = $filegif;
        return $this;
    }

    public function getFilevideo(): ?\Symfony\Component\HttpFoundation\File\UploadedFile
    {
        return $this->filevideo;
    }

    public function setFilevideo(?\Symfony\Component\HttpFoundation\File\UploadedFile $filevideo): self
    {
        $this->filevideo = $filevideo;
        return $this;
    }

    public function getUrlvideo(): ?string
    {
        return $this->urlvideo;
    }

    public function setUrlvideo(?string $urlvideo): self
    {
        $this->urlvideo = $urlvideo;
        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
        }
        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        $this->comments->removeElement($comment);
        return $this;
    }

    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
        }
        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        $this->transactions->removeElement($transaction);
        return $this;
    }

    public function getComment(): bool
    {
        return $this->comment;
    }

    public function setComment(bool $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function setTags(?string $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getAngry(): int
    {
        return $this->angry;
    }

    public function setAngry(int $angry): self
    {
        $this->angry = $angry;
        return $this;
    }

    public function getHaha(): int
    {
        return $this->haha;
    }

    public function setHaha(int $haha): self
    {
        $this->haha = $haha;
        return $this;
    }

    public function getLike(): int
    {
        return $this->like;
    }

    public function setLike(int $like): self
    {
        $this->like = $like;
        return $this;
    }

    public function getLove(): int
    {
        return $this->love;
    }

    public function setLove(int $love): self
    {
        $this->love = $love;
        return $this;
    }

    public function getSad(): int
    {
        return $this->sad;
    }

    public function setSad(int $sad): self
    {
        $this->sad = $sad;
        return $this;
    }

    public function getWoow(): int
    {
        return $this->woow;
    }

    public function setWoow(int $woow): self
    {
        $this->woow = $woow;
        return $this;
    }

    public function getVideo(): Media
    {
        return $this->video;
    }

    public function setVideo(Media $video): self
    {
        $this->video = $video;
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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getClear(): string
    {
        return base64_decode($this->title);
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function setViews(int $views): self
    {
        $this->views = $views;
        return $this;
    }

    public function getViewsnumber(): string
    {
        return $this->number_format_short($this->views) . " View(s)";
    }

    public function getDownloadsnumber(): string
    {
        return $this->number_format_short($this->downloads) . " Share(s)";
    }

    public function number_format_short(int $n): string
    {
        if ($n === 0) {
            return '0';
        }
        if ($n < 1000) {
            return (string) $n;
        } elseif ($n >= 1000 && $n < 1000000) {
            return floor($n / 1000) . 'K+';
        } elseif ($n >= 1000000 && $n < 1000000000) {
            return floor($n / 1000000) . 'M+';
        } elseif ($n >= 1000000000 && $n < 1000000000000) {
            return floor($n / 1000000000) . 'B+';
        }
        return floor($n / 1000000000000) . 'T+';
    }
}