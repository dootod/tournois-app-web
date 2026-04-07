<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop min_poule and max_poule columns from parametre';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE parametre DROP COLUMN min_poule, DROP COLUMN max_poule');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE parametre ADD min_poule INT NOT NULL DEFAULT 3, ADD max_poule INT NOT NULL DEFAULT 6');
    }
}
