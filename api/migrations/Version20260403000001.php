<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Finalisation : suppression equipe_id participant, ajout champs match_tour, suppression tables obsolètes';
    }

    public function up(Schema $schema): void
    {
        // Supprimer equipe_id de participant
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B116D861B89');
        $this->addSql('DROP INDEX IDX_D79F6B116D861B89 ON participant');
        $this->addSql('ALTER TABLE participant DROP COLUMN equipe_id');

        // Ajouter participant1_id, participant2_id, tatami, heure_debut, heure_fin sur match_tour
        $this->addSql('ALTER TABLE match_tour ADD participant1_id INT DEFAULT NULL, ADD participant2_id INT DEFAULT NULL, ADD tatami INT DEFAULT NULL, ADD heure_debut TIME DEFAULT NULL, ADD heure_fin TIME DEFAULT NULL');
        $this->addSql('ALTER TABLE match_tour ADD CONSTRAINT FK_match_p1 FOREIGN KEY (participant1_id) REFERENCES participant (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE match_tour ADD CONSTRAINT FK_match_p2 FOREIGN KEY (participant2_id) REFERENCES participant (id) ON DELETE SET NULL');

        // Supprimer les tables obsolètes
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('DROP TABLE IF EXISTS planning');
        $this->addSql('DROP TABLE IF EXISTS tatami');
        $this->addSql('DROP TABLE IF EXISTS equipe');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_tour DROP FOREIGN KEY FK_match_p1');
        $this->addSql('ALTER TABLE match_tour DROP FOREIGN KEY FK_match_p2');
        $this->addSql('ALTER TABLE match_tour DROP COLUMN participant1_id, DROP COLUMN participant2_id, DROP COLUMN tatami, DROP COLUMN heure_debut, DROP COLUMN heure_fin');
    }
}
