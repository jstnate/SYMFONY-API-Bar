<?php

namespace App\Entity;

use AllowDynamicProperties;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;

#[AllowDynamicProperties]
#[ApiResource(
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['write']],
    forceEager: false
)]
#[GetCollection(
    security: "is_granted('ROLE_PATRON') || is_granted('ROLE_SERVEUR') || is_granted('ROLE_BARMAN')"
)]
#[Post(
    security: "is_granted('ROLE_SERVEUR')"
)]
#[Get(
    security: "is_granted('ROLE_PATRON') || is_granted('ROLE_SERVEUR') || is_granted('ROLE_BARMAN')"
)]
#[Put(
    security: "is_granted('ROLE_SERVEUR') || is_granted('ROLE_BARMAN')"
)]
#[Patch(
    security: "is_granted('ROLE_SERVEUR') || is_granted('ROLE_BARMAN')"
)]
#[Delete(
    security: "is_granted('ROLE_SERVEUR')"
)]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('read')]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column]
    #[Groups(['read', 'write'])]
    private ?int $tableNumber = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read', 'write'])]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[Groups(['read', 'write'])]
    private ?User $serveur = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[Groups(['read', 'write'])]
    private ?User $barman = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups('read')]
    private ?\DateTimeInterface $paymentDate = null;

    /**
     * @var Collection<int, Drink>
     */
    #[ORM\ManyToMany(targetEntity: Drink::class, inversedBy: 'orders')]
    #[Groups(['read', 'write'])]
    private Collection $drink;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->drink = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        if ($this->status === "payée") return $this;
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTableNumber(): ?int
    {
        return $this->tableNumber;
    }

    public function setTableNumber(int $tableNumber): static
    {
        if ($this->status === "payée") return $this;
        $this->tableNumber = $tableNumber;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $allowedStatus = ['en cours de préparation', 'prête', 'payée'];
        if ($this->status === "payée") return $this;

        if (!in_array($status, $allowedStatus)) {
            throw new \InvalidArgumentException(sprintf('Invalid status "%s". Allowed status are %s.', $status, implode(', ', $allowedStatus)));
        }

        $this->status = $status;
        if ($status === "payée") {
            $this->setPaymentDate();
        }
        return $this;
    }

    public function getServeur(): ?User
    {
        return $this->serveur;
    }

    public function setServeur(?User $serveur): static
    {
        $this->serveur = $serveur;

        return $this;
    }

    public function getBarman(): ?User
    {
        return $this->barman;
    }

    public function setBarman(?User $barman): static
    {
        if ($this->status === "payée") return $this;
        $this->barman = $barman;

        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(): static
    {
        $this->paymentDate = new \DateTime();

        return $this;
    }

    /**
     * @return Collection<int, Drink>
     */
    public function getDrink(): Collection
    {
        return $this->drink;
    }

    public function addDrink(Drink $drink): static
    {
        if ($this->status === "payée") return $this;
        $this->drink->add($drink);

        return $this;
    }

    public function removeDrink(Drink $drink): static
    {
        $this->drink->removeElement($drink);

        return $this;
    }
}
