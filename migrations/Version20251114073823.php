<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251114073823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add creator column to category and product tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category ADD creator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C161220EA6 FOREIGN KEY (creator_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_64C19C161220EA6 ON category (creator_id)');
        $this->addSql('ALTER INDEX idx_3f2070412469de2 RENAME TO IDX_40124B1012469DE2');
        $this->addSql('ALTER TABLE product ADD creator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD61220EA6 FOREIGN KEY (creator_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D34A04AD61220EA6 ON product (creator_id)');
        $this->addSql('ALTER INDEX idx_1846db704584665a RENAME TO IDX_466113F64584665A');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE product DROP CONSTRAINT FK_D34A04AD61220EA6');
        $this->addSql('DROP INDEX IDX_D34A04AD61220EA6');
        $this->addSql('ALTER TABLE product DROP creator_id');
        $this->addSql('ALTER INDEX idx_40124b1012469de2 RENAME TO idx_3f2070412469de2');
        $this->addSql('ALTER INDEX idx_466113f64584665a RENAME TO idx_1846db704584665a');
        $this->addSql('ALTER TABLE category DROP CONSTRAINT FK_64C19C161220EA6');
        $this->addSql('DROP INDEX IDX_64C19C161220EA6');
        $this->addSql('ALTER TABLE category DROP creator_id');
    }
}
