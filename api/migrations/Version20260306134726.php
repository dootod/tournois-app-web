<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260306134726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE adherent (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) NOT NULL, prenom VARCHAR(50) NOT NULL, date_naissance DATE NOT NULL, date_adhesion DATE NOT NULL, ceinture VARCHAR(50) NOT NULL, poids NUMERIC(15, 2) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE equipe (id INT AUTO_INCREMENT NOT NULL, rang_equipe INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE match_tour (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE parametre (id INT AUTO_INCREMENT NOT NULL, temps_combat NUMERIC(15, 2) NOT NULL, max_equipes INT NOT NULL, min_poule INT NOT NULL, max_participants INT NOT NULL, max_poule INT NOT NULL, nb_surfaces INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE participant (id INT AUTO_INCREMENT NOT NULL, rang_poule INT DEFAULT NULL, rang_tournoi INT DEFAULT NULL, points_tournoi NUMERIC(15, 2) DEFAULT NULL, poule INT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE planning (id INT AUTO_INCREMENT NOT NULL, heure_debut TIME NOT NULL, heare_fin TIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE poule (id INT AUTO_INCREMENT NOT NULL, categorie VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE score (id INT AUTO_INCREMENT NOT NULL, gagnant TINYINT NOT NULL, score INT NOT NULL, disqualification TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tatami (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tournoi (id INT AUTO_INCREMENT NOT NULL, equipe TINYINT NOT NULL, date DATE NOT NULL, etat VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE adherent');
        $this->addSql('DROP TABLE equipe');
        $this->addSql('DROP TABLE match_tour');
        $this->addSql('DROP TABLE parametre');
        $this->addSql('DROP TABLE participant');
        $this->addSql('DROP TABLE planning');
        $this->addSql('DROP TABLE poule');
        $this->addSql('DROP TABLE score');
        $this->addSql('DROP TABLE tatami');
        $this->addSql('DROP TABLE tournoi');
    }
}
