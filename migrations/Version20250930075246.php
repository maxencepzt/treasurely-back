<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250930075246 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE hunt_type (id SERIAL NOT NULL, title VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE picture (id SERIAL NOT NULL, image BYTEA NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE team (id SERIAL NOT NULL, owner_id INT DEFAULT NULL, image_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(500) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C4E0A61F7E3C61F9 ON team (owner_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4E0A61F3DA5256D ON team (image_id)');
        $this->addSql('CREATE TABLE team_user (team_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(team_id, user_id))');
        $this->addSql('CREATE INDEX IDX_5C722232296CD8AE ON team_user (team_id)');
        $this->addSql('CREATE INDEX IDX_5C722232A76ED395 ON team_user (user_id)');
        $this->addSql('CREATE TABLE treasure_hunt (id SERIAL NOT NULL, team_id INT NOT NULL, image_id INT DEFAULT NULL, owner_id INT NOT NULL, title VARCHAR(20) NOT NULL, description TEXT DEFAULT NULL, public BOOLEAN NOT NULL, difficulty INT NOT NULL, riddle_count INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1643FB81296CD8AE ON treasure_hunt (team_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1643FB813DA5256D ON treasure_hunt (image_id)');
        $this->addSql('CREATE INDEX IDX_1643FB817E3C61F9 ON treasure_hunt (owner_id)');
        $this->addSql('CREATE TABLE treasure_hunt_hunt_type (treasure_hunt_id INT NOT NULL, hunt_type_id INT NOT NULL, PRIMARY KEY(treasure_hunt_id, hunt_type_id))');
        $this->addSql('CREATE INDEX IDX_4A05F2399E2B9CCD ON treasure_hunt_hunt_type (treasure_hunt_id)');
        $this->addSql('CREATE INDEX IDX_4A05F2396CB936B5 ON treasure_hunt_hunt_type (hunt_type_id)');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, profile_picture_id INT NOT NULL, nickname VARCHAR(50) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, firstname VARCHAR(100) NOT NULL, lastname VARCHAR(100) NOT NULL, email VARCHAR(50) NOT NULL, birth_date DATE NOT NULL, phone VARCHAR(13) NOT NULL, activated BOOLEAN NOT NULL, creation_date DATE NOT NULL, last_login TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, public BOOLEAN NOT NULL, gender VARCHAR(255) NOT NULL, total_time INT NOT NULL, total_hunt INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649292E8AE2 ON "user" (profile_picture_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQUE_IDENTIFIERS ON "user" (nickname, email)');
        $this->addSql('COMMENT ON COLUMN "user".creation_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F3DA5256D FOREIGN KEY (image_id) REFERENCES picture (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_user ADD CONSTRAINT FK_5C722232296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_user ADD CONSTRAINT FK_5C722232A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treasure_hunt ADD CONSTRAINT FK_1643FB81296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treasure_hunt ADD CONSTRAINT FK_1643FB813DA5256D FOREIGN KEY (image_id) REFERENCES picture (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treasure_hunt ADD CONSTRAINT FK_1643FB817E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treasure_hunt_hunt_type ADD CONSTRAINT FK_4A05F2399E2B9CCD FOREIGN KEY (treasure_hunt_id) REFERENCES treasure_hunt (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treasure_hunt_hunt_type ADD CONSTRAINT FK_4A05F2396CB936B5 FOREIGN KEY (hunt_type_id) REFERENCES hunt_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649292E8AE2 FOREIGN KEY (profile_picture_id) REFERENCES picture (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE team DROP CONSTRAINT FK_C4E0A61F7E3C61F9');
        $this->addSql('ALTER TABLE team DROP CONSTRAINT FK_C4E0A61F3DA5256D');
        $this->addSql('ALTER TABLE team_user DROP CONSTRAINT FK_5C722232296CD8AE');
        $this->addSql('ALTER TABLE team_user DROP CONSTRAINT FK_5C722232A76ED395');
        $this->addSql('ALTER TABLE treasure_hunt DROP CONSTRAINT FK_1643FB81296CD8AE');
        $this->addSql('ALTER TABLE treasure_hunt DROP CONSTRAINT FK_1643FB813DA5256D');
        $this->addSql('ALTER TABLE treasure_hunt DROP CONSTRAINT FK_1643FB817E3C61F9');
        $this->addSql('ALTER TABLE treasure_hunt_hunt_type DROP CONSTRAINT FK_4A05F2399E2B9CCD');
        $this->addSql('ALTER TABLE treasure_hunt_hunt_type DROP CONSTRAINT FK_4A05F2396CB936B5');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649292E8AE2');
        $this->addSql('DROP TABLE hunt_type');
        $this->addSql('DROP TABLE picture');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_user');
        $this->addSql('DROP TABLE treasure_hunt');
        $this->addSql('DROP TABLE treasure_hunt_hunt_type');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
