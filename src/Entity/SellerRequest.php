<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class SellerRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $shopName;

    #[ORM\Column(type: 'text', nullable: true)]
    private $shopDescription;

    #[ORM\Column(type: 'string', length: 255)]
    private $contactEmail;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $contactPhone;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    private $user;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeInterface $date): self
    {
        $this->createdAt = $date;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getShopName(): ?string
    {
        return $this->shopName;
    }
    public function setShopName(string $shopName): self
    {
        $this->shopName = $shopName;
        return $this;
    }
    public function getShopDescription(): ?string
    {
        return $this->shopDescription;
    }
    public function setShopDescription(?string $desc): self
    {
        $this->shopDescription = $desc;
        return $this;
    }
    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }
    public function setContactEmail(string $email): self
    {
        $this->contactEmail = $email;
        return $this;
    }
    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }
    public function setContactPhone(?string $phone): self
    {
        $this->contactPhone = $phone;
        return $this;
    }
    public function getUser()
    {
        return $this->user;
    }
    public function setUser($user): self
    {
        $this->user = $user;
        return $this;
    }

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }
}
