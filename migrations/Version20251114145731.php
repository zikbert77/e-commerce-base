<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251114145731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add OrderItem model';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE order_item (id SERIAL NOT NULL, related_order_id INT NOT NULL, product_id INT NOT NULL, product_title VARCHAR(255) NOT NULL, qty SMALLINT NOT NULL, price INT NOT NULL, subtotal_amount INT NOT NULL, discount_amount INT NOT NULL, tax_amount INT NOT NULL, total_amount INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_52EA1F092B1C2395 ON order_item (related_order_id)');
        $this->addSql('CREATE INDEX IDX_52EA1F094584665A ON order_item (product_id)');
        $this->addSql('COMMENT ON COLUMN order_item.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN order_item.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F092B1C2395 FOREIGN KEY (related_order_id) REFERENCES "order" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_item DROP CONSTRAINT FK_52EA1F092B1C2395');
        $this->addSql('ALTER TABLE order_item DROP CONSTRAINT FK_52EA1F094584665A');
        $this->addSql('DROP TABLE order_item');
    }
}
