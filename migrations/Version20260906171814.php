<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260906171814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cascades de suppression, et passage de riddle.choices/answers de array PHP a JSON.';
    }

    public function up(Schema $schema): void
    {
        // Les colonnes choices/answers contenaient des tableaux serialises par PHP
        // (type Doctrine ARRAY). PostgreSQL ne sait pas les convertir en JSON tout seul :
        // on reecrit les valeurs avant de changer le type de colonne.
        foreach ($this->connection->fetchAllAssociative('SELECT id, choices, answers FROM riddle') as $row) {
            $updates = [];
            foreach (['choices', 'answers'] as $column) {
                $raw = $row[$column];
                if (null === $raw || '' === $raw) {
                    continue;
                }
                if (null !== json_decode($raw, true) && JSON_ERROR_NONE === json_last_error()) {
                    continue; // deja au format JSON
                }
                $value = @unserialize($raw, ['allowed_classes' => false]);
                $updates[$column] = json_encode(false === $value ? [] : $value, \JSON_UNESCAPED_UNICODE);
            }
            if ($updates) {
                $set = implode(', ', array_map(static fn (string $c): string => $c.' = ?', array_keys($updates)));
                $this->connection->executeStatement(
                    'UPDATE riddle SET '.$set.' WHERE id = ?',
                    [...array_values($updates), $row['id']]
                );
            }
        }

        $this->addSql('ALTER TABLE participate_hunt DROP CONSTRAINT FK_FE6A59C72585A34B');
        $this->addSql('ALTER TABLE participate_hunt DROP CONSTRAINT FK_FE6A59C79785124');
        $this->addSql('ALTER TABLE participate_hunt DROP CONSTRAINT FK_FE6A59C7A7DC5C81');
        $this->addSql('ALTER TABLE participate_hunt ADD CONSTRAINT FK_FE6A59C72585A34B FOREIGN KEY (hunt_id) REFERENCES treasure_hunt (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participate_hunt ADD CONSTRAINT FK_FE6A59C79785124 FOREIGN KEY (current_riddle_id) REFERENCES riddle (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participate_hunt ADD CONSTRAINT FK_FE6A59C7A7DC5C81 FOREIGN KEY (hunter_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participate_riddle DROP CONSTRAINT FK_5783ED5AA7DC5C81');
        $this->addSql('ALTER TABLE participate_riddle DROP CONSTRAINT FK_5783ED5AD25EE088');
        $this->addSql('ALTER TABLE participate_riddle ADD CONSTRAINT FK_5783ED5AA7DC5C81 FOREIGN KEY (hunter_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participate_riddle ADD CONSTRAINT FK_5783ED5AD25EE088 FOREIGN KEY (riddle_id) REFERENCES riddle (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE riddle ALTER choices TYPE JSON USING choices::json');
        $this->addSql('ALTER TABLE riddle ALTER answers TYPE JSON USING answers::json');
        $this->addSql('COMMENT ON COLUMN riddle.choices IS NULL');
        $this->addSql('COMMENT ON COLUMN riddle.answers IS NULL');
        $this->addSql('DROP INDEX idx_75ea56e016ba31db');
        $this->addSql('DROP INDEX idx_75ea56e0e3bd61ce');
        $this->addSql('DROP INDEX idx_75ea56e0fb7336f0');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE riddle ALTER choices TYPE TEXT');
        $this->addSql('ALTER TABLE riddle ALTER answers TYPE TEXT');
        $this->addSql('COMMENT ON COLUMN riddle.choices IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN riddle.answers IS \'(DC2Type:array)\'');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750');
        $this->addSql('CREATE INDEX idx_75ea56e016ba31db ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX idx_75ea56e0e3bd61ce ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX idx_75ea56e0fb7336f0 ON messenger_messages (queue_name)');
        $this->addSql('ALTER TABLE participate_hunt DROP CONSTRAINT fk_fe6a59c7a7dc5c81');
        $this->addSql('ALTER TABLE participate_hunt DROP CONSTRAINT fk_fe6a59c72585a34b');
        $this->addSql('ALTER TABLE participate_hunt DROP CONSTRAINT fk_fe6a59c79785124');
        $this->addSql('ALTER TABLE participate_hunt ADD CONSTRAINT fk_fe6a59c7a7dc5c81 FOREIGN KEY (hunter_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participate_hunt ADD CONSTRAINT fk_fe6a59c72585a34b FOREIGN KEY (hunt_id) REFERENCES treasure_hunt (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participate_hunt ADD CONSTRAINT fk_fe6a59c79785124 FOREIGN KEY (current_riddle_id) REFERENCES riddle (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participate_riddle DROP CONSTRAINT fk_5783ed5aa7dc5c81');
        $this->addSql('ALTER TABLE participate_riddle DROP CONSTRAINT fk_5783ed5ad25ee088');
        $this->addSql('ALTER TABLE participate_riddle ADD CONSTRAINT fk_5783ed5aa7dc5c81 FOREIGN KEY (hunter_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participate_riddle ADD CONSTRAINT fk_5783ed5ad25ee088 FOREIGN KEY (riddle_id) REFERENCES riddle (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
