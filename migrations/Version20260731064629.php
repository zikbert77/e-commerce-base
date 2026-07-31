<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731064629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Bind tables to store';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cart ADD store_id INT NOT NULL');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B7B092A811 FOREIGN KEY (store_id) REFERENCES store (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_BA388B7B092A811 ON cart (store_id)');
        $this->addSql('ALTER TABLE category ADD store_id INT NOT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1B092A811 FOREIGN KEY (store_id) REFERENCES store (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_64C19C1B092A811 ON category (store_id)');
        $this->addSql('ALTER TABLE "order" ADD store_id INT NOT NULL');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F5299398B092A811 FOREIGN KEY (store_id) REFERENCES store (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_F5299398B092A811 ON "order" (store_id)');
        $this->addSql('ALTER TABLE product ADD store_id INT NOT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADB092A811 FOREIGN KEY (store_id) REFERENCES store (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D34A04ADB092A811 ON product (store_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP CONSTRAINT FK_D34A04ADB092A811');
        $this->addSql('DROP INDEX IDX_D34A04ADB092A811');
        $this->addSql('ALTER TABLE product DROP store_id');
        $this->addSql('ALTER TABLE category DROP CONSTRAINT FK_64C19C1B092A811');
        $this->addSql('DROP INDEX IDX_64C19C1B092A811');
        $this->addSql('ALTER TABLE category DROP store_id');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F5299398B092A811');
        $this->addSql('DROP INDEX IDX_F5299398B092A811');
        $this->addSql('ALTER TABLE "order" DROP store_id');
        $this->addSql('ALTER TABLE cart DROP CONSTRAINT FK_BA388B7B092A811');
        $this->addSql('DROP INDEX IDX_BA388B7B092A811');
        $this->addSql('ALTER TABLE cart DROP store_id');
    }
}
