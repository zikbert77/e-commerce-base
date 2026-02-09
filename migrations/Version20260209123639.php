<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260209123639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique constraint for session-based active carts to prevent race conditions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_session_active_cart ON cart (session_id, status) WHERE (status = 1 AND session_id IS NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F5299398539B0606 ON "order" (uid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX uniq_session_active_cart');
        $this->addSql('DROP INDEX UNIQ_F5299398539B0606');
    }
}
