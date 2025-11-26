<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251126150948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participate_hunt ALTER last_participate TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN participate_hunt.last_participate IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE team ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN team.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE participate_hunt ALTER last_participate TYPE DATE');
        $this->addSql('COMMENT ON COLUMN participate_hunt.last_participate IS \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE team ALTER created_at TYPE DATE');
        $this->addSql('COMMENT ON COLUMN team.created_at IS \'(DC2Type:date_immutable)\'');
    }
}
