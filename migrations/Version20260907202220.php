<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907202220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Table des sessions de la façade concepteur et de l\'administration (PdoSessionHandler).';
    }

    public function up(Schema $schema): void
    {
        // Schéma déclaré par PdoSessionHandler::configureSchema(), tel que Doctrine le génère.
        $this->addSql('CREATE TABLE sessions (sess_id VARCHAR(128) NOT NULL, sess_data BYTEA NOT NULL, sess_lifetime INT NOT NULL, sess_time INT NOT NULL, PRIMARY KEY(sess_id))');
        $this->addSql('CREATE INDEX sess_lifetime_idx ON sessions (sess_lifetime)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE sessions');
    }
}
