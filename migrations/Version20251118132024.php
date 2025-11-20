<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251118132024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participate_hunt ADD player_team_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE participate_hunt ADD CONSTRAINT FK_FE6A59C7489827EB FOREIGN KEY (player_team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_FE6A59C7489827EB ON participate_hunt (player_team_id)');
        $this->addSql('DROP INDEX uniq_c4e0a61f3da5256d');
        $this->addSql('ALTER TABLE team ADD discriminator VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE team ADD code VARCHAR(24) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_C4E0A61F3DA5256D ON team (image_id)');
        $this->addSql('ALTER TABLE treasure_hunt ADD designer_team_id INT NOT NULL');
        $this->addSql('ALTER TABLE treasure_hunt ADD CONSTRAINT FK_1643FB81E0EFCD71 FOREIGN KEY (designer_team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_1643FB81E0EFCD71 ON treasure_hunt (designer_team_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE treasure_hunt DROP CONSTRAINT FK_1643FB81E0EFCD71');
        $this->addSql('DROP INDEX IDX_1643FB81E0EFCD71');
        $this->addSql('ALTER TABLE treasure_hunt DROP designer_team_id');
        $this->addSql('DROP INDEX IDX_C4E0A61F3DA5256D');
        $this->addSql('ALTER TABLE team DROP discriminator');
        $this->addSql('ALTER TABLE team DROP code');
        $this->addSql('CREATE UNIQUE INDEX uniq_c4e0a61f3da5256d ON team (image_id)');
        $this->addSql('ALTER TABLE participate_hunt DROP CONSTRAINT FK_FE6A59C7489827EB');
        $this->addSql('DROP INDEX IDX_FE6A59C7489827EB');
        $this->addSql('ALTER TABLE participate_hunt DROP player_team_id');
    }
}
