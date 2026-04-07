<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260320125333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE parametre ADD tournoi_id INT NOT NULL');
        $this->addSql('ALTER TABLE parametre ADD CONSTRAINT FK_ACC79041F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ACC79041F607770A ON parametre (tournoi_id)');
        $this->addSql('ALTER TABLE planning CHANGE heare_fin heure_fin TIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE parametre DROP FOREIGN KEY FK_ACC79041F607770A');
        $this->addSql('DROP INDEX UNIQ_ACC79041F607770A ON parametre');
        $this->addSql('ALTER TABLE parametre DROP tournoi_id');
        $this->addSql('ALTER TABLE planning CHANGE heure_fin heare_fin TIME NOT NULL');
    }
}
