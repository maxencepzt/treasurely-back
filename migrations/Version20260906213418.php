<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260906213418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tentatives notees par enigme, compteur de tentatives par participation, et choix du concepteur de reveler le nombre de bonnes reponses d un QCM.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participate_riddle ADD attempts INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE riddle ADD max_scoring_attempts INT DEFAULT 3 NOT NULL');
        $this->addSql('ALTER TABLE riddle ADD reveal_answer_count BOOLEAN DEFAULT true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE riddle DROP max_scoring_attempts');
        $this->addSql('ALTER TABLE riddle DROP reveal_answer_count');
        $this->addSql('ALTER TABLE participate_riddle DROP attempts');
    }
}
