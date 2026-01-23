<?php

namespace App\Entity;

use App\Repository\CouponRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
#[ORM\Table(name:  'coupon')]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ? int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Le code promo ne peut pas être vide')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Le code doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le code ne peut pas dépasser {{ limit }} caractères'
    )]
    private ? string $code = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank(message: 'La réduction ne peut pas être vide')]
    #[Assert\Positive(message: 'La réduction doit être positive')]
    private ?float $discount = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: ['percentage', 'fixed'], message: 'Type invalide')]
    private ?string $type = 'percentage'; // 'percentage' ou 'fixed'

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive(message: 'La limite d\'utilisation doit être positive')]
    private ?int $usageLimit = null;

    #[ORM\Column(type: 'integer')]
    private int $usedCount = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'coupons')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $seller = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->usedCount = 0;
        $this->isActive = true;
    }

    // Getters et Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper($code); // Toujours en majuscules
        return $this;
    }

    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function setDiscount(float $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getUsageLimit(): ?int
    {
        return $this->usageLimit;
    }

    public function setUsageLimit(? int $usageLimit): self
    {
        $this->usageLimit = $usageLimit;
        return $this;
    }

    public function getUsedCount(): int
    {
        return $this->usedCount;
    }

    public function setUsedCount(int $usedCount): self
    {
        $this->usedCount = $usedCount;
        return $this;
    }

    public function incrementUsedCount(): self
    {
        $this->usedCount++;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getSeller(): ?User
    {
        return $this->seller;
    }

    public function setSeller(?User $seller): self
    {
        $this->seller = $seller;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    // Méthodes utiles

    public function isValid(): bool
    {
        // Vérifier si le coupon est actif
        if (! $this->isActive) {
            return false;
        }

        // Vérifier la date d'expiration
        if ($this->expiresAt && $this->expiresAt < new \DateTime()) {
            return false;
        }

        // Vérifier la limite d'utilisation
        if ($this->usageLimit !== null && $this->usedCount >= $this->usageLimit) {
            return false;
        }

        return true;
    }

    public function getDiscountAmount(float $cartTotal): float
    {
        if ($this->type === 'percentage') {
            return ($cartTotal * $this->discount) / 100;
        }

        return min($this->discount, $cartTotal); // Réduction fixe (max = total panier)
    }

    public function __toString(): string
    {
        return $this->code ??  '';
    }
}