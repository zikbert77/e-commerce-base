<?php

namespace App\Entity;

use Andante\TimestampableBundle\Timestampable\TimestampableInterface;
use Andante\TimestampableBundle\Timestampable\TimestampableTrait;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'childCategories')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $childCategories;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $status = null;

    /**
     * @var Collection<int, CategoryInfo>
     */
    #[ORM\OneToMany(targetEntity: CategoryInfo::class, mappedBy: 'category', orphanRemoval: true)]
    private Collection $categoryInfos;

    #[ORM\ManyToOne]
    private ?User $creator = null;

    public function __construct()
    {
        $this->childCategories = new ArrayCollection();
        $this->categoryInfos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildCategories(): Collection
    {
        return $this->childCategories;
    }

    public function addChildCategory(self $childCategory): static
    {
        if (!$this->childCategories->contains($childCategory)) {
            $this->childCategories->add($childCategory);
            $childCategory->setParent($this);
        }

        return $this;
    }

    public function removeChildCategory(self $childCategory): static
    {
        if ($this->childCategories->removeElement($childCategory)) {
            // set the owning side to null (unless already changed)
            if ($childCategory->getParent() === $this) {
                $childCategory->setParent(null);
            }
        }

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
     * @return Collection<int, CategoryInfo>
     */
    public function getCategoryInfos(): Collection
    {
        return $this->categoryInfos;
    }

    public function addCategoryInfo(CategoryInfo $categoryInfo): static
    {
        if (!$this->categoryInfos->contains($categoryInfo)) {
            $this->categoryInfos->add($categoryInfo);
            $categoryInfo->setCategory($this);
        }

        return $this;
    }

    public function removeCategoryInfo(CategoryInfo $categoryInfo): static
    {
        if ($this->categoryInfos->removeElement($categoryInfo)) {
            // set the owning side to null (unless already changed)
            if ($categoryInfo->getCategory() === $this) {
                $categoryInfo->setCategory(null);
            }
        }

        return $this;
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
