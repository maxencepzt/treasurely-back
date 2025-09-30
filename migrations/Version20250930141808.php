<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250930141808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE participate_hunt (id SERIAL NOT NULL, hunter_id INT NOT NULL, hunt_id INT NOT NULL, rate INT DEFAULT NULL, time INT NOT NULL, score INT NOT NULL, finished BOOLEAN NOT NULL, last_participate DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FE6A59C7A7DC5C81 ON participate_hunt (hunter_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FE6A59C72585A34B ON participate_hunt (hunt_id)');
        $this->addSql('COMMENT ON COLUMN participate_hunt.last_participate IS \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE participate_hunt ADD CONSTRAINT FK_FE6A59C7A7DC5C81 FOREIGN KEY (hunter_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participate_hunt ADD CONSTRAINT FK_FE6A59C72585A34B FOREIGN KEY (hunt_id) REFERENCES treasure_hunt (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE participate_hunt DROP CONSTRAINT FK_FE6A59C7A7DC5C81');
        $this->addSql('ALTER TABLE participate_hunt DROP CONSTRAINT FK_FE6A59C72585A34B');
        $this->addSql('DROP TABLE participate_hunt');
    }
}
