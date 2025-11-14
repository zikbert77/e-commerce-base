<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251114090402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique index to prevent duplicates of products for same cart';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_cart_product ON cart_item (cart_id, product_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_cart_product');
    }
}
