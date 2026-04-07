<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppression équipes/planning/tatami, ajout paiement participant, prix/IBAN tournoi, tatami+horaires match';
    }

    public function up(Schema $schema): void
    {
        // Ajouter prix_participation et iban sur tournoi
        $this->addSql('ALTER TABLE tournoi ADD prix_participation NUMERIC(10, 2) DEFAULT NULL, ADD iban VARCHAR(50) DEFAULT NULL');

        // Supprimer la colonne equipe de tournoi
        $this->addSql('ALTER TABLE tournoi DROP COLUMN equipe');

        // Supprimer max_equipes et nb_surfaces de parametre, renommer nb_surfaces en nb_tatamis
        $this->addSql('ALTER TABLE parametre DROP COLUMN max_equipes');
        $this->addSql('ALTER TABLE parametre CHANGE nb_surfaces nb_tatamis INT NOT NULL');

        // Ajouter paye sur participant, supprimer equipe_id
        $this->addSql('ALTER TABLE participant ADD paye TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B116D861B89');
        $this->addSql('DROP INDEX IDX_D79F6B116D861B89 ON participant');
        $this->addSql('ALTER TABLE participant DROP COLUMN equipe_id');

        // Ajouter participant1_id, participant2_id, tatami, heure_debut, heure_fin sur match_tour
        $this->addSql('ALTER TABLE match_tour ADD participant1_id INT DEFAULT NULL, ADD participant2_id INT DEFAULT NULL, ADD tatami INT DEFAULT NULL, ADD heure_debut TIME DEFAULT NULL, ADD heure_fin TIME DEFAULT NULL');
        $this->addSql('ALTER TABLE match_tour ADD CONSTRAINT FK_match_p1 FOREIGN KEY (participant1_id) REFERENCES participant (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE match_tour ADD CONSTRAINT FK_match_p2 FOREIGN KEY (participant2_id) REFERENCES participant (id) ON DELETE SET NULL');

        // Supprimer les tables planning et tatami et equipe
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('DROP TABLE IF EXISTS planning');
        $this->addSql('DROP TABLE IF EXISTS tatami');
        $this->addSql('DROP TABLE IF EXISTS equipe');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        // Restaurer (partiel)
        $this->addSql('ALTER TABLE tournoi ADD equipe TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE tournoi DROP COLUMN prix_participation, DROP COLUMN iban');
        $this->addSql('ALTER TABLE parametre ADD max_equipes INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE parametre CHANGE nb_tatamis nb_surfaces INT NOT NULL');
        $this->addSql('ALTER TABLE participant DROP COLUMN paye');
        $this->addSql('ALTER TABLE match_tour DROP FOREIGN KEY FK_match_p1');
        $this->addSql('ALTER TABLE match_tour DROP FOREIGN KEY FK_match_p2');
        $this->addSql('ALTER TABLE match_tour DROP COLUMN participant1_id, DROP COLUMN participant2_id, DROP COLUMN tatami, DROP COLUMN heure_debut, DROP COLUMN heure_fin');
    }
}
