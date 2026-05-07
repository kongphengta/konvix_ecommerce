<?php

namespace App\Entity;

use App\Repository\SellerEarningRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SellerEarningRepository::class)]
class SellerEarning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Seller $seller = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\Column(type: 'float')]
    private float $grossAmount = 0.0;

    #[ORM\Column(type: 'float')]
    private float $commissionAmount = 0.0;

    #[ORM\Column(type: 'float')]
    private float $netAmount = 0.0;

    #[ORM\Column(type: 'float')]
    private float $commissionRate = 0.10;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 20)]
    private string $status = 'pending'; // pending, paid

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getSeller(): ?Seller { return $this->seller; }
    public function setSeller(?Seller $seller): static { $this->seller = $seller; return $this; }

    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): static { $this->order = $order; return $this; }

    public function getGrossAmount(): float { return $this->grossAmount; }
    public function setGrossAmount(float $grossAmount): static { $this->grossAmount = $grossAmount; return $this; }

    public function getCommissionAmount(): float { return $this->commissionAmount; }
    public function setCommissionAmount(float $commissionAmount): static { $this->commissionAmount = $commissionAmount; return $this; }

    public function getNetAmount(): float { return $this->netAmount; }
    public function setNetAmount(float $netAmount): static { $this->netAmount = $netAmount; return $this; }

    public function getCommissionRate(): float { return $this->commissionRate; }
    public function setCommissionRate(float $commissionRate): static { $this->commissionRate = $commissionRate; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getPaidAt(): ?\DateTimeImmutable { return $this->paidAt; }
    public function setPaidAt(?\DateTimeImmutable $paidAt): static { $this->paidAt = $paidAt; return $this; }
}
