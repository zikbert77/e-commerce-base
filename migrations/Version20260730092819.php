<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260730092819 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add timestamps to store_domain table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE store_domain ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE store_domain ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN store_domain.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN store_domain.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE store_domain DROP created_at');
        $this->addSql('ALTER TABLE store_domain DROP updated_at');
    }
}
