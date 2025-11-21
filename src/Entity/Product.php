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
     * @var Collection<int, ProductInfo>
     */
    #[ORM\OneToMany(targetEntity: ProductInfo::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $productInfos;

    #[ORM\ManyToOne]
    private ?User $creator = null;

    public function __construct()
    {
        $this->productInfos = new ArrayCollection();
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
     * @return Collection<int, ProductInfo>
     */
    public function getProductInfos(): Collection
    {
        return $this->productInfos;
    }

    public function addProductInfo(ProductInfo $productInfo): static
    {
        if (!$this->productInfos->contains($productInfo)) {
            $this->productInfos->add($productInfo);
            $productInfo->setProduct($this);
        }

        return $this;
    }

    public function removeProductInfo(ProductInfo $productInfo): static
    {
        if ($this->productInfos->removeElement($productInfo)) {
            // set the owning side to null (unless already changed)
            if ($productInfo->getProduct() === $this) {
                $productInfo->setProduct(null);
            }
        }

        return $this;
    }

    public function getProductInfoByLocale(string $locale): ?ProductInfo
    {
        if (empty($this->productInfos)) {
            return null;
        }

        return $this->productInfos->filter(function (ProductInfo $productInfo) use ($locale) {
            return $productInfo->getLocale() === $locale;
        })->first();
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;

        return $this;
    }
}
