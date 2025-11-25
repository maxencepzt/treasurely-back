<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124155009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participate_hunt ADD current_riddle_id INT NOT NULL');
        $this->addSql('ALTER TABLE participate_hunt ADD CONSTRAINT FK_FE6A59C79785124 FOREIGN KEY (current_riddle_id) REFERENCES riddle (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_FE6A59C79785124 ON participate_hunt (current_riddle_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE participate_hunt DROP CONSTRAINT FK_FE6A59C79785124');
        $this->addSql('DROP INDEX IDX_FE6A59C79785124');
        $this->addSql('ALTER TABLE participate_hunt DROP current_riddle_id');
    }
}
