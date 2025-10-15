<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prix = null;

    #[ORM\Column]
    private ?int $quantiteRestante = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $images = [];

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'produits')]
    private Collection $categorie;

    /**
     * @var Collection<int, ArticlePanier>
     */
    #[ORM\OneToMany(targetEntity: ArticlePanier::class, mappedBy: 'produit')]
    private Collection $articlePanier;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 200)]
    private ?string $descriptionShortcut = null;

    public function __construct()
    {
        $this->categorie = new ArrayCollection();
        $this->articlePanier = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getQuantiteRestante(): ?int
    {
        return $this->quantiteRestante;
    }

    public function setQuantiteRestante(int $quantiteRestante): static
    {
        $this->quantiteRestante = $quantiteRestante;

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

    public function getImages(): array
    {
        return $this->images;
    }

    public function setImages(array $images): static
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategorie(): Collection
    {
        return $this->categorie;
    }

    public function addCategorie(Category $categorie): static
    {
        if (!$this->categorie->contains($categorie)) {
            $this->categorie->add($categorie);
        }

        return $this;
    }

    public function removeCategorie(Category $categorie): static
    {
        $this->categorie->removeElement($categorie);

        return $this;
    }

    /**
     * @return Collection<int, ArticlePanier>
     */
    public function getArticlePanier(): Collection
    {
        return $this->articlePanier;
    }

    public function addArticlePanier(ArticlePanier $articlePanier): static
    {
        if (!$this->articlePanier->contains($articlePanier)) {
            $this->articlePanier->add($articlePanier);
            $articlePanier->setProduit($this);
        }

        return $this;
    }

    public function removeArticlePanier(ArticlePanier $articlePanier): static
    {
        if ($this->articlePanier->removeElement($articlePanier)) {
            // set the owning side to null (unless already changed)
            if ($articlePanier->getProduit() === $this) {
                $articlePanier->setProduit(null);
            }
        }

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getDescriptionShortcut(): ?string
    {
        return $this->descriptionShortcut;
    }


    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateDescriptionShortcut(): void
    {
        if ($this->description) {
            $this->descriptionShortcut = substr(strip_tags($this->description), 0, 200);
        }
    }
    // Pour retirer du stock a chaque vente
    public function decrementStock(int $qty): self
    {
        $qty = max(0, $qty);
        $current = (int) ($this->quantiteRestante ?? 0);
        $this->quantiteRestante = max(0, $current - $qty);
        return $this;
    }

    //    En cas de remboursement ou annulation 
    public function incrementStock(int $qty): self
    {
        $qty = max(0, $qty);
        $current = (int) ($this->quantiteRestante ?? 0);
        $this->quantiteRestante = $current + $qty;
        return $this;
    }
}
