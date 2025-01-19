<?php

namespace App\Entity;

use App\Repository\VideoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VideoRepository::class)]
#[ORM\Table(name: "videos")]  // 🔥 Ajout du nom personnalisé de la table
class Video
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $api_id = null;

    #[ORM\Column(length: 255)]
    private ?string $source = null;


    #[ORM\Column(nullable: true)]
    private ?array $data = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTime $updated_at = null;


    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable(); // 🔥 Définit `createdAt` automatiquement à la création
        $this->updated_at = new \DateTime(); // 🔥 Définit `updatedAt` à la création
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApiId(): ?string
    {
        return $this->api_id;
    }

    public function setApiId(string $id): static
    {
        $this->api_id = $id;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }
}
