<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajouter ON DELETE CASCADE à match_tour → poule';
    }

    public function up(Schema $schema): void
    {
        // Mettre à jour match_tour -> poule avec ON DELETE CASCADE
        $this->addSql('ALTER TABLE match_tour DROP FOREIGN KEY FK_BBE481B26596FD8');
        $this->addSql('ALTER TABLE match_tour ADD CONSTRAINT FK_BBE481B26596FD8 FOREIGN KEY (poule_id) REFERENCES poule (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_tour DROP FOREIGN KEY FK_BBE481B26596FD8');
        $this->addSql('ALTER TABLE match_tour ADD CONSTRAINT FK_BBE481B26596FD8 FOREIGN KEY (poule_id) REFERENCES poule (id)');
    }
}
