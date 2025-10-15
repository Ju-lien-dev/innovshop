<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $reference = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $total = null;

    #[ORM\ManyToOne]
    private ?Panier $panier = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shipFullName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shipAddress = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $shipZip = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $shipCity = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $shipCountry = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?User $user = null;

    /**
     * @var Collection<int, ArticleCommande>
     */
    #[ORM\OneToMany(targetEntity: ArticleCommande::class, mappedBy: 'commande', orphanRemoval: true)]
    private Collection $articles;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getPanier(): ?Panier
    {
        return $this->panier;
    }

    public function setPanier(?Panier $panier): static
    {
        $this->panier = $panier;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, ArticleCommande>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(ArticleCommande $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setCommande($this);
        }

        return $this;
    }

    public function removeArticle(ArticleCommande $article): static
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getCommande() === $this) {
                $article->setCommande(null);
            }
        }

        return $this;
    }

    public function getShipFullName(): ?string
    {
        return $this->shipFullName;
    }
    public function setShipFullName(?string $v): self
    {
        $this->shipFullName = $v;
        return $this;
    }

    public function getShipAddress(): ?string
    {
        return $this->shipAddress;
    }
    public function setShipAddress(?string $v): self
    {
        $this->shipAddress = $v;
        return $this;
    }

    public function getShipZip(): ?string
    {
        return $this->shipZip;
    }
    public function setShipZip(?string $v): self
    {
        $this->shipZip = $v;
        return $this;
    }

    public function getShipCity(): ?string
    {
        return $this->shipCity;
    }
    public function setShipCity(?string $v): self
    {
        $this->shipCity = $v;
        return $this;
    }

    public function getShipCountry(): ?string
    {
        return $this->shipCountry;
    }
    public function setShipCountry(?string $v): self
    {
        $this->shipCountry = $v;
        return $this;
    }
}
