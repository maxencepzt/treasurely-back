<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250929131312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, profile_picture_id INT NOT NULL, nickname VARCHAR(50) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, firstname VARCHAR(100) NOT NULL, lastname VARCHAR(100) NOT NULL, email VARCHAR(50) NOT NULL, birth_date DATE NOT NULL, phone VARCHAR(13) NOT NULL, activated BOOLEAN NOT NULL, creation_date DATE NOT NULL, last_login TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, public BOOLEAN NOT NULL, gender VARCHAR(255) NOT NULL, total_time INT NOT NULL, total_hunt INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649292E8AE2 ON "user" (profile_picture_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQUE_IDENTIFIERS ON "user" (nickname, email)');
        $this->addSql('COMMENT ON COLUMN "user".creation_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649292E8AE2 FOREIGN KEY (profile_picture_id) REFERENCES picture (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649292E8AE2');
        $this->addSql('DROP TABLE "user"');
    }
}
