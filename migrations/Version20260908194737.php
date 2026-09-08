<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Les demandes d'adhésion aux équipes de joueurs (TeamJoinRequest) : une par joueur et par
 * équipe, tranchée par le propriétaire.
 */
final class Version20260908194737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Join requests to player teams (team_join_request)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE team_join_request (id SERIAL NOT NULL, team_id INT NOT NULL, user_id INT NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, decided_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E1B4E93D296CD8AE ON team_join_request (team_id)');
        $this->addSql('CREATE INDEX IDX_E1B4E93DA76ED395 ON team_join_request (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_TEAM_JOIN_REQUEST ON team_join_request (team_id, user_id)');
        $this->addSql('COMMENT ON COLUMN team_join_request.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN team_join_request.decided_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE team_join_request ADD CONSTRAINT FK_E1B4E93D296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_join_request ADD CONSTRAINT FK_E1B4E93DA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE team_join_request DROP CONSTRAINT FK_E1B4E93D296CD8AE');
        $this->addSql('ALTER TABLE team_join_request DROP CONSTRAINT FK_E1B4E93DA76ED395');
        $this->addSql('DROP TABLE team_join_request');
    }
}
