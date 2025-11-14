<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250722102916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create product info table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product_info (id SERIAL NOT NULL, product_id INT NOT NULL, slug VARCHAR(255) NOT NULL, locale VARCHAR(6) NOT NULL, title VARCHAR(255) NOT NULL, short_description TEXT DEFAULT NULL, description TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1846DB704584665A ON product_info (product_id)');
        $this->addSql('COMMENT ON COLUMN product_info.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN product_info.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE product_info ADD CONSTRAINT FK_1846DB704584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_info DROP CONSTRAINT FK_1846DB704584665A');
        $this->addSql('DROP TABLE product_info');
    }
}
