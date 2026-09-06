<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260906215836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Horodatage de modification des chasses, et passage des dates de creation et de derniere participation en date-heure.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participate_hunt ALTER last_participate TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN participate_hunt.last_participate IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE team ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN team.created_at IS \'(DC2Type:datetime_immutable)\'');
        // Les chasses existantes prennent leur date de creation comme derniere modification.
        $this->addSql('ALTER TABLE treasure_hunt ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE treasure_hunt SET updated_at = created_at');
        $this->addSql('ALTER TABLE treasure_hunt ALTER updated_at SET NOT NULL');
        $this->addSql('ALTER TABLE treasure_hunt ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN treasure_hunt.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN treasure_hunt.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE participate_hunt ALTER last_participate TYPE DATE');
        $this->addSql('COMMENT ON COLUMN participate_hunt.last_participate IS \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE team ALTER created_at TYPE DATE');
        $this->addSql('COMMENT ON COLUMN team.created_at IS \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE treasure_hunt DROP updated_at');
        $this->addSql('ALTER TABLE treasure_hunt ALTER created_at TYPE DATE');
        $this->addSql('COMMENT ON COLUMN treasure_hunt.created_at IS \'(DC2Type:date_immutable)\'');
    }
}
