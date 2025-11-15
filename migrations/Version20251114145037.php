<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251114145037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Order model';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE "order" (id SERIAL NOT NULL, related_user_id INT DEFAULT NULL, uid VARCHAR(30) NOT NULL, status INT NOT NULL, subtotal_amount INT NOT NULL, discount_amount INT NOT NULL, shipping_cost_amount INT NOT NULL, tax_amount INT NOT NULL, total_amount INT NOT NULL, paid_amount INT NOT NULL, customer_first_name VARCHAR(120) DEFAULT NULL, customer_last_name VARCHAR(120) DEFAULT NULL, customer_email VARCHAR(120) DEFAULT NULL, customer_phone VARCHAR(60) DEFAULT NULL, shipping_address VARCHAR(255) NOT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, shipped_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, canceled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F529939898771930 ON "order" (related_user_id)');
        $this->addSql('COMMENT ON COLUMN "order".paid_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "order".shipped_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "order".completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "order".canceled_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "order".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "order".updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F529939898771930 FOREIGN KEY (related_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F529939898771930');
        $this->addSql('DROP TABLE "order"');
    }
}
