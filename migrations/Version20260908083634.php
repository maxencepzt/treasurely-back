<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260908083634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participate_hunt DROP CONSTRAINT FK_FE6A59C7489827EB');
        $this->addSql('ALTER TABLE participate_hunt ADD CONSTRAINT FK_FE6A59C7489827EB FOREIGN KEY (player_team_id) REFERENCES team (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE participate_hunt DROP CONSTRAINT fk_fe6a59c7489827eb');
        $this->addSql('ALTER TABLE participate_hunt ADD CONSTRAINT fk_fe6a59c7489827eb FOREIGN KEY (player_team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
