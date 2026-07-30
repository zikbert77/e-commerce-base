<?php

namespace App\Entity;

use Andante\TimestampableBundle\Timestampable\TimestampableInterface;
use Andante\TimestampableBundle\Timestampable\TimestampableTrait;
use App\Enum\BaseStatus;
use App\Repository\StoreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StoreRepository::class)]
class Store implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(enumType: BaseStatus::class)]
    private ?BaseStatus $status = null;

    /**
     * @var Collection<int, StoreDomain>
     */
    #[ORM\OneToMany(targetEntity: StoreDomain::class, mappedBy: 'store', orphanRemoval: true)]
    private Collection $storeDomains;

    public function __construct()
    {
        $this->storeDomains = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStatus(): ?BaseStatus
    {
        return $this->status;
    }

    public function setStatus(BaseStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, StoreDomain>
     */
    public function getStoreDomains(): Collection
    {
        return $this->storeDomains;
    }

    public function addStoreDomain(StoreDomain $storeDomain): static
    {
        if (!$this->storeDomains->contains($storeDomain)) {
            $this->storeDomains->add($storeDomain);
            $storeDomain->setStore($this);
        }

        return $this;
    }

    public function removeStoreDomain(StoreDomain $storeDomain): static
    {
        if ($this->storeDomains->removeElement($storeDomain)) {
            // set the owning side to null (unless already changed)
            if ($storeDomain->getStore() === $this) {
                $storeDomain->setStore(null);
            }
        }

        return $this;
    }
}
