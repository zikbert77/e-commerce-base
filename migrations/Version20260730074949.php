<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260730074949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create store and store_domain tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE store (id SERIAL NOT NULL, title VARCHAR(255) NOT NULL, status INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN store.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN store.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE store_domain (id SERIAL NOT NULL, store_id INT NOT NULL, domain VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C3BE889DA7A91E0B ON store_domain (domain)');
        $this->addSql('CREATE INDEX IDX_C3BE889DB092A811 ON store_domain (store_id)');
        $this->addSql('ALTER TABLE store_domain ADD CONSTRAINT FK_C3BE889DB092A811 FOREIGN KEY (store_id) REFERENCES store (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE store_domain DROP CONSTRAINT FK_C3BE889DB092A811');
        $this->addSql('DROP TABLE store');
        $this->addSql('DROP TABLE store_domain');
    }
}
