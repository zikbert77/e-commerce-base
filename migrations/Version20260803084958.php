<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803084958 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removed strict relation between product and category';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP CONSTRAINT fk_d34a04ad12469de2');
        $this->addSql('DROP INDEX idx_d34a04ad12469de2');
        $this->addSql('ALTER TABLE product DROP category_id');
        $this->addSql('ALTER TABLE store_domain ALTER created_at DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE product ADD category_id INT NOT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT fk_d34a04ad12469de2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_d34a04ad12469de2 ON product (category_id)');
        $this->addSql('ALTER TABLE store_domain ALTER created_at SET DEFAULT CURRENT_TIMESTAMP');
    }
}
