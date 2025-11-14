<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251114075025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SEO meta tags for product and category models';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category_info ADD seo_title VARCHAR(60) DEFAULT NULL');
        $this->addSql('ALTER TABLE category_info ADD seo_description VARCHAR(160) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_info ADD seo_title VARCHAR(60) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_info ADD seo_description VARCHAR(160) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_info DROP seo_title');
        $this->addSql('ALTER TABLE product_info DROP seo_description');
        $this->addSql('ALTER TABLE category_info DROP seo_title');
        $this->addSql('ALTER TABLE category_info DROP seo_description');
    }
}
