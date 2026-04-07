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
        // Cette migration n'a aucune opération (déjà faites par Version20260403000000)
    }

    public function down(Schema $schema): void
    {
        // Aucune opération à inverser
    }
}
