<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811090155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add template tables and link template to store';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE store_template_config (id SERIAL NOT NULL, store_id INT NOT NULL, template_id INT NOT NULL, config JSONB DEFAULT \'{}\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BE1E4F405DA0FB8 ON store_template_config (template_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_store_template ON store_template_config (store_id, template_id)');
        $this->addSql('COMMENT ON COLUMN store_template_config.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN store_template_config.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE template (id SERIAL NOT NULL, code VARCHAR(64) NOT NULL, title VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL, default_config JSONB NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97601F8377153098 ON template (code)');
        $this->addSql('COMMENT ON COLUMN template.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN template.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE store_template_config ADD CONSTRAINT FK_BE1E4F40B092A811 FOREIGN KEY (store_id) REFERENCES store (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE store_template_config ADD CONSTRAINT FK_BE1E4F405DA0FB8 FOREIGN KEY (template_id) REFERENCES template (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE store ADD template_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE store ADD CONSTRAINT FK_FF5758775DA0FB8 FOREIGN KEY (template_id) REFERENCES template (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_FF5758775DA0FB8 ON store (template_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE store DROP CONSTRAINT FK_FF5758775DA0FB8');
        $this->addSql('ALTER TABLE store_template_config DROP CONSTRAINT FK_BE1E4F40B092A811');
        $this->addSql('ALTER TABLE store_template_config DROP CONSTRAINT FK_BE1E4F405DA0FB8');
        $this->addSql('DROP TABLE store_template_config');
        $this->addSql('DROP TABLE template');
        $this->addSql('DROP INDEX IDX_FF5758775DA0FB8');
        $this->addSql('ALTER TABLE store DROP template_id');
    }
}
