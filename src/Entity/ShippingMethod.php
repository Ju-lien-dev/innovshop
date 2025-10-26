<?php

namespace App\Entity;

use App\Repository\ShippingMethodRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShippingMethodRepository::class)]
class ShippingMethod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $amountCents = null;

    #[ORM\Column]
    private ?int $minDays = null;

    #[ORM\Column]
    private ?int $maxDays = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getAmountCents(): ?int
    {
        return $this->amountCents;
    }

    public function setAmountCents(int $amountCents): static
    {
        $this->amountCents = $amountCents;

        return $this;
    }

    public function getMinDays(): ?int
    {
        return $this->minDays;
    }

    public function setMinDays(int $minDays): static
    {
        $this->minDays = $minDays;

        return $this;
    }

    public function getMaxDays(): ?int
    {
        return $this->maxDays;
    }

    public function setMaxDays(int $maxDays): static
    {
        $this->maxDays = $maxDays;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }
    public function getDelayAsText(): string
    {
        $min = $this->getMinDays();
        $max = $this->getMaxDays();

        if ($min === null && $max === null) {
            return '—';
        }
        if ($min === null) {
            return sprintf('%d j', $max);
        }
        if ($max === null) {
            return sprintf('%d j', $min);
        }
        if ($min === $max) {
            return sprintf('%d j', $min);
        }
        return sprintf('%d–%d j', $min, $max);
    }
}
