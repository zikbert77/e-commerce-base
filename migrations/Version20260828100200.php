<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828100200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add enabled flag to product_info';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_info ADD enabled BOOLEAN NOT NULL DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_info DROP enabled');
    }
}
