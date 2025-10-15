<?php

namespace App\Entity;

use App\Repository\PanierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PanierRepository::class)]
class Panier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $prixTotal = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTime $updatedAt = null;

    #[ORM\OneToOne(inversedBy: 'panier', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    /**
     * @var Collection<int, ArticlePanier>
     */
    #[ORM\OneToMany(targetEntity: ArticlePanier::class, mappedBy: 'panier', orphanRemoval: true)]
    private Collection $articlePaniers;

    public function __construct()
    {
        $this->articlePaniers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrixTotal(): ?string
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(string $prixTotal): static
    {
        $this->prixTotal = $prixTotal;

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

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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
     * @return Collection<int, ArticlePanier>
     */
    public function getArticlePaniers(): Collection
    {
        return $this->articlePaniers;
    }

    public function addArticlePanier(ArticlePanier $articlePanier): static
    {
        if (!$this->articlePaniers->contains($articlePanier)) {
            $this->articlePaniers->add($articlePanier);
            $articlePanier->setPanier($this);
        }

        return $this;
    }

    public function removeArticlePanier(ArticlePanier $articlePanier): static
    {
        if ($this->articlePaniers->removeElement($articlePanier)) {
            // set the owning side to null (unless already changed)
            if ($articlePanier->getPanier() === $this) {
                $articlePanier->setPanier(null);
            }
        }

        return $this;
    }
}
