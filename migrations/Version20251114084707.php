<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251114084707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Cart model';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cart (id SERIAL NOT NULL, user_id INT DEFAULT NULL, session_id VARCHAR(60) DEFAULT NULL, status SMALLINT DEFAULT 1 NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BA388B7A76ED395 ON cart (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_active_cart ON cart (user_id, status) WHERE (status = 1)');
        $this->addSql('COMMENT ON COLUMN cart.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B7A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE cart DROP CONSTRAINT FK_BA388B7A76ED395');
        $this->addSql('DROP TABLE cart');
    }
}
