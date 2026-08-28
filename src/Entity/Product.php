<?php

namespace App\Entity;

use Andante\TimestampableBundle\Timestampable\TimestampableInterface;
use Andante\TimestampableBundle\Timestampable\TimestampableTrait;
use App\Entity\Interface\StoreScopedInterface;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product implements TimestampableInterface, StoreScopedInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $status = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'store_id', referencedColumnName: 'id', nullable: false)]
    private ?Store $store = null;

    /**
     * @var Collection<int, ProductInfo>
     */
    #[ORM\OneToMany(targetEntity: ProductInfo::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $productInfos;

    #[ORM\ManyToOne]
    private ?User $creator = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'products')]
    private Collection $categories;

    public function __construct()
    {
        $this->productInfos = new ArrayCollection();
        $this->categories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStore(): ?Store
    {
        return $this->store;
    }

    public function setStore(?Store $store): static
    {
        $this->store = $store;

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
        $match = $this->productInfos->filter(function (ProductInfo $productInfo) use ($locale) {
            return $productInfo->getLocale() === $locale;
        })->first();

        return $match ?: null;
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

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->addProduct($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            $category->removeProduct($this);
        }

        return $this;
    }
}
