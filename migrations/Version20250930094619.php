<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250930094619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE treasure_hunt (id SERIAL NOT NULL, team_id INT NOT NULL, image_id INT DEFAULT NULL, owner_id INT NOT NULL, title VARCHAR(20) NOT NULL, description VARCHAR(3000) DEFAULT NULL, public BOOLEAN NOT NULL, difficulty INT NOT NULL, riddle_count INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1643FB81296CD8AE ON treasure_hunt (team_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1643FB813DA5256D ON treasure_hunt (image_id)');
        $this->addSql('CREATE INDEX IDX_1643FB817E3C61F9 ON treasure_hunt (owner_id)');
        $this->addSql('CREATE TABLE treasure_hunt_hunt_type (treasure_hunt_id INT NOT NULL, hunt_type_id INT NOT NULL, PRIMARY KEY(treasure_hunt_id, hunt_type_id))');
        $this->addSql('CREATE INDEX IDX_4A05F2399E2B9CCD ON treasure_hunt_hunt_type (treasure_hunt_id)');
        $this->addSql('CREATE INDEX IDX_4A05F2396CB936B5 ON treasure_hunt_hunt_type (hunt_type_id)');
        $this->addSql('ALTER TABLE treasure_hunt ADD CONSTRAINT FK_1643FB81296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treasure_hunt ADD CONSTRAINT FK_1643FB813DA5256D FOREIGN KEY (image_id) REFERENCES picture (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treasure_hunt ADD CONSTRAINT FK_1643FB817E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treasure_hunt_hunt_type ADD CONSTRAINT FK_4A05F2399E2B9CCD FOREIGN KEY (treasure_hunt_id) REFERENCES treasure_hunt (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treasure_hunt_hunt_type ADD CONSTRAINT FK_4A05F2396CB936B5 FOREIGN KEY (hunt_type_id) REFERENCES hunt_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE treasure_hunt DROP CONSTRAINT FK_1643FB81296CD8AE');
        $this->addSql('ALTER TABLE treasure_hunt DROP CONSTRAINT FK_1643FB813DA5256D');
        $this->addSql('ALTER TABLE treasure_hunt DROP CONSTRAINT FK_1643FB817E3C61F9');
        $this->addSql('ALTER TABLE treasure_hunt_hunt_type DROP CONSTRAINT FK_4A05F2399E2B9CCD');
        $this->addSql('ALTER TABLE treasure_hunt_hunt_type DROP CONSTRAINT FK_4A05F2396CB936B5');
        $this->addSql('DROP TABLE treasure_hunt');
        $this->addSql('DROP TABLE treasure_hunt_hunt_type');
    }
}
