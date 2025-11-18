<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251118132904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE treasure_hunt DROP CONSTRAINT fk_1643fb81296cd8ae');
        $this->addSql('DROP INDEX idx_1643fb81296cd8ae');
        $this->addSql('ALTER TABLE treasure_hunt DROP team_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE treasure_hunt ADD team_id INT NOT NULL');
        $this->addSql('ALTER TABLE treasure_hunt ADD CONSTRAINT fk_1643fb81296cd8ae FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_1643fb81296cd8ae ON treasure_hunt (team_id)');
    }
}
