<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\CodePromo;

#[ORM\Entity]
class CodePromoUsage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: CodePromo::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?CodePromo $codePromo = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $usedAt;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }
    public function getCodePromo(): ?CodePromo
    {
        return $this->codePromo;
    }
    public function setCodePromo(CodePromo $codePromo): self
    {
        $this->codePromo = $codePromo;
        return $this;
    }
    public function getUsedAt(): \DateTimeInterface
    {
        return $this->usedAt;
    }
    public function setUsedAt(\DateTimeInterface $usedAt): self
    {
        $this->usedAt = $usedAt;
        return $this;
    }
}
