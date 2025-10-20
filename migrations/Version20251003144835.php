<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251003144835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE hunt_type_treasure_hunt (hunt_type_id INT NOT NULL, treasure_hunt_id INT NOT NULL, PRIMARY KEY(hunt_type_id, treasure_hunt_id))');
        $this->addSql('CREATE INDEX IDX_F83068A26CB936B5 ON hunt_type_treasure_hunt (hunt_type_id)');
        $this->addSql('CREATE INDEX IDX_F83068A29E2B9CCD ON hunt_type_treasure_hunt (treasure_hunt_id)');
        $this->addSql('ALTER TABLE hunt_type_treasure_hunt ADD CONSTRAINT FK_F83068A26CB936B5 FOREIGN KEY (hunt_type_id) REFERENCES hunt_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE hunt_type_treasure_hunt ADD CONSTRAINT FK_F83068A29E2B9CCD FOREIGN KEY (treasure_hunt_id) REFERENCES treasure_hunt (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treasure_hunt_hunt_type DROP CONSTRAINT fk_4a05f2396cb936b5');
        $this->addSql('ALTER TABLE treasure_hunt_hunt_type DROP CONSTRAINT fk_4a05f2399e2b9ccd');
        $this->addSql('DROP TABLE treasure_hunt_hunt_type');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE treasure_hunt_hunt_type (treasure_hunt_id INT NOT NULL, hunt_type_id INT NOT NULL, PRIMARY KEY(treasure_hunt_id, hunt_type_id))');
        $this->addSql('CREATE INDEX idx_4a05f2396cb936b5 ON treasure_hunt_hunt_type (hunt_type_id)');
        $this->addSql('CREATE INDEX idx_4a05f2399e2b9ccd ON treasure_hunt_hunt_type (treasure_hunt_id)');
        $this->addSql('ALTER TABLE treasure_hunt_hunt_type ADD CONSTRAINT fk_4a05f2396cb936b5 FOREIGN KEY (hunt_type_id) REFERENCES hunt_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treasure_hunt_hunt_type ADD CONSTRAINT fk_4a05f2399e2b9ccd FOREIGN KEY (treasure_hunt_id) REFERENCES treasure_hunt (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE hunt_type_treasure_hunt DROP CONSTRAINT FK_F83068A26CB936B5');
        $this->addSql('ALTER TABLE hunt_type_treasure_hunt DROP CONSTRAINT FK_F83068A29E2B9CCD');
        $this->addSql('DROP TABLE hunt_type_treasure_hunt');
    }
}
