<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajouter cascades ON DELETE CASCADE pour les relations de tournoi (poule, tournoi_participant)';
    }

    public function up(Schema $schema): void
    {
        // Mettre à jour poule -> tournoi avec ON DELETE CASCADE
        $this->addSql('ALTER TABLE poule DROP FOREIGN KEY FK_FA1FEB40F607770A');
        $this->addSql('ALTER TABLE poule ADD CONSTRAINT FK_FA1FEB40F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');

        // Mettre à jour tournoi_participant -> tournoi avec ON DELETE CASCADE
        $this->addSql('ALTER TABLE tournoi_participant DROP FOREIGN KEY FK_9C531479F607770A');
        $this->addSql('ALTER TABLE tournoi_participant ADD CONSTRAINT FK_9C531479F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Révertir les cascades
        $this->addSql('ALTER TABLE poule DROP FOREIGN KEY FK_FA1FEB40F607770A');
        $this->addSql('ALTER TABLE poule ADD CONSTRAINT FK_FA1FEB40F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id)');

        $this->addSql('ALTER TABLE tournoi_participant DROP FOREIGN KEY FK_9C531479F607770A');
        $this->addSql('ALTER TABLE tournoi_participant ADD CONSTRAINT FK_9C531479F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id)');
    }
}

