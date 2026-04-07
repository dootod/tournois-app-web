<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ genre (masculin, feminin, mixte) sur adherent';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE adherent ADD genre VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE adherent DROP COLUMN genre');
    }
}
