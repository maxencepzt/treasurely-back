<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250930135419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE riddle (id SERIAL NOT NULL, hunt_id INT DEFAULT NULL, title VARCHAR(20) NOT NULL, description VARCHAR(1000) NOT NULL, difficulty INT NOT NULL, order_number INT NOT NULL, discriminator VARCHAR(255) NOT NULL, code VARCHAR(20) DEFAULT NULL, choices TEXT DEFAULT NULL, answers TEXT DEFAULT NULL, answer VARCHAR(100) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6C00AA812585A34B ON riddle (hunt_id)');
        $this->addSql('COMMENT ON COLUMN riddle.choices IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN riddle.answers IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE riddle ADD CONSTRAINT FK_6C00AA812585A34B FOREIGN KEY (hunt_id) REFERENCES treasure_hunt (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE riddle DROP CONSTRAINT FK_6C00AA812585A34B');
        $this->addSql('DROP TABLE riddle');
    }
}
