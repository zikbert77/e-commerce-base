<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827153231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link store to user via store_user pivot table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE store_user (store_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(store_id, user_id))');
        $this->addSql('CREATE INDEX IDX_6F2A7887B092A811 ON store_user (store_id)');
        $this->addSql('CREATE INDEX IDX_6F2A7887A76ED395 ON store_user (user_id)');
        $this->addSql('ALTER TABLE store_user ADD CONSTRAINT FK_6F2A7887B092A811 FOREIGN KEY (store_id) REFERENCES store (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE store_user ADD CONSTRAINT FK_6F2A7887A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE store_user DROP CONSTRAINT FK_6F2A7887B092A811');
        $this->addSql('ALTER TABLE store_user DROP CONSTRAINT FK_6F2A7887A76ED395');
        $this->addSql('DROP TABLE store_user');
    }
}
