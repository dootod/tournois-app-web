<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout équipes (avec nom+tournoi), phase/round/equipes sur match_tour, equipe bool sur tournoi';
    }

    public function up(Schema $schema): void
    {
        // Restaurer equipe (bool) sur tournoi
        $this->addSql('ALTER TABLE tournoi ADD equipe TINYINT(1) NOT NULL DEFAULT 0');

        // Créer la table equipe
        $this->addSql('CREATE TABLE equipe (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(100) NOT NULL,
            rang_equipe INT DEFAULT NULL,
            tournoi_id INT NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_equipe_tournoi (tournoi_id),
            CONSTRAINT FK_equipe_tournoi FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Ajouter equipe_id sur participant
        $this->addSql('ALTER TABLE participant ADD equipe_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_participant_equipe FOREIGN KEY (equipe_id) REFERENCES equipe (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_participant_equipe ON participant (equipe_id)');

        // Ajouter phase, round, equipe1_id, equipe2_id, score_equipe1, score_equipe2 sur match_tour
        $this->addSql('ALTER TABLE match_tour
            ADD phase VARCHAR(20) NOT NULL DEFAULT \'qualification\',
            ADD round VARCHAR(50) DEFAULT NULL,
            ADD equipe1_id INT DEFAULT NULL,
            ADD equipe2_id INT DEFAULT NULL,
            ADD score_equipe1 INT DEFAULT NULL,
            ADD score_equipe2 INT DEFAULT NULL
        ');
        $this->addSql('ALTER TABLE match_tour ADD CONSTRAINT FK_match_equipe1 FOREIGN KEY (equipe1_id) REFERENCES equipe (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE match_tour ADD CONSTRAINT FK_match_equipe2 FOREIGN KEY (equipe2_id) REFERENCES equipe (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_tour DROP FOREIGN KEY FK_match_equipe1');
        $this->addSql('ALTER TABLE match_tour DROP FOREIGN KEY FK_match_equipe2');
        $this->addSql('ALTER TABLE match_tour DROP COLUMN phase, DROP COLUMN round, DROP COLUMN equipe1_id, DROP COLUMN equipe2_id, DROP COLUMN score_equipe1, DROP COLUMN score_equipe2');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_participant_equipe');
        $this->addSql('ALTER TABLE participant DROP COLUMN equipe_id');
        $this->addSql('DROP TABLE equipe');
        $this->addSql('ALTER TABLE tournoi DROP COLUMN equipe');
    }
}
