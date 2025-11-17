<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117151658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_fe6a59c72585a34b');
        $this->addSql('DROP INDEX uniq_fe6a59c7a7dc5c81');
        $this->addSql('ALTER TABLE participate_hunt ALTER hunter_id DROP NOT NULL');
        $this->addSql('ALTER TABLE participate_hunt ALTER hunt_id DROP NOT NULL');
        $this->addSql('CREATE INDEX IDX_FE6A59C7A7DC5C81 ON participate_hunt (hunter_id)');
        $this->addSql('CREATE INDEX IDX_FE6A59C72585A34B ON participate_hunt (hunt_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX IDX_FE6A59C7A7DC5C81');
        $this->addSql('DROP INDEX IDX_FE6A59C72585A34B');
        $this->addSql('ALTER TABLE participate_hunt ALTER hunter_id SET NOT NULL');
        $this->addSql('ALTER TABLE participate_hunt ALTER hunt_id SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_fe6a59c72585a34b ON participate_hunt (hunt_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_fe6a59c7a7dc5c81 ON participate_hunt (hunter_id)');
    }
}
