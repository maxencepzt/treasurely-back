<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251001082420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE participate_riddle (id SERIAL NOT NULL, hunter_id INT NOT NULL, riddle_id INT NOT NULL, start_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, finish_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, score INT NOT NULL, last_participate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5783ED5AA7DC5C81 ON participate_riddle (hunter_id)');
        $this->addSql('CREATE INDEX IDX_5783ED5AD25EE088 ON participate_riddle (riddle_id)');
        $this->addSql('COMMENT ON COLUMN participate_riddle.start_time IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN participate_riddle.finish_time IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE participate_riddle ADD CONSTRAINT FK_5783ED5AA7DC5C81 FOREIGN KEY (hunter_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participate_riddle ADD CONSTRAINT FK_5783ED5AD25EE088 FOREIGN KEY (riddle_id) REFERENCES riddle (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE participate_riddle DROP CONSTRAINT FK_5783ED5AA7DC5C81');
        $this->addSql('ALTER TABLE participate_riddle DROP CONSTRAINT FK_5783ED5AD25EE088');
        $this->addSql('DROP TABLE participate_riddle');
    }
}
