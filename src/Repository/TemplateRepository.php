<?php

namespace App\Repository;

use App\Entity\Template;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Template>
 */
class TemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Template::class);
    }

    public function getDefault(): Template
    {
        $template = $this->findOneBy(['code' => 'default', 'isActive' => true]);
        if (empty($template)) {
            throw new \RuntimeException('Default template (code=default) is not configured.');
        }

        return $template;
    }
}
