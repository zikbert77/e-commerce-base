<?php

namespace App\Entity;

use Andante\TimestampableBundle\Timestampable\TimestampableInterface;
use Andante\TimestampableBundle\Timestampable\TimestampableTrait;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $status = null;

    /**
     * @var Collection<int, ProductTranslation>
     */
    #[ORM\OneToMany(targetEntity: ProductTranslation::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $productTranslations;

    public function __construct()
    {
        $this->productTranslations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, ProductTranslation>
     */
    public function getProductTranslations(): Collection
    {
        return $this->productTranslations;
    }

    public function addProductTranslation(ProductTranslation $productTranslation): static
    {
        if (!$this->productTranslations->contains($productTranslation)) {
            $this->productTranslations->add($productTranslation);
            $productTranslation->setProduct($this);
        }

        return $this;
    }

    public function removeProductTranslation(ProductTranslation $productTranslation): static
    {
        if ($this->productTranslations->removeElement($productTranslation)) {
            // set the owning side to null (unless already changed)
            if ($productTranslation->getProduct() === $this) {
                $productTranslation->setProduct(null);
            }
        }

        return $this;
    }
}
