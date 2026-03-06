<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260306135729 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE score_match_tour (score_id INT NOT NULL, match_tour_id INT NOT NULL, INDEX IDX_FA3483A312EB0A51 (score_id), INDEX IDX_FA3483A3624BD22C (match_tour_id), PRIMARY KEY (score_id, match_tour_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tournoi_participant (tournoi_id INT NOT NULL, participant_id INT NOT NULL, INDEX IDX_9C531479F607770A (tournoi_id), INDEX IDX_9C5314799D1C3019 (participant_id), PRIMARY KEY (tournoi_id, participant_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE score_match_tour ADD CONSTRAINT FK_FA3483A312EB0A51 FOREIGN KEY (score_id) REFERENCES score (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE score_match_tour ADD CONSTRAINT FK_FA3483A3624BD22C FOREIGN KEY (match_tour_id) REFERENCES match_tour (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournoi_participant ADD CONSTRAINT FK_9C531479F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournoi_participant ADD CONSTRAINT FK_9C5314799D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE match_tour ADD poule_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE match_tour ADD CONSTRAINT FK_BBE481B26596FD8 FOREIGN KEY (poule_id) REFERENCES poule (id)');
        $this->addSql('CREATE INDEX IDX_BBE481B26596FD8 ON match_tour (poule_id)');
        $this->addSql('ALTER TABLE participant ADD adherent_id INT DEFAULT NULL, ADD equipe_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B1125F06C53 FOREIGN KEY (adherent_id) REFERENCES adherent (id)');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B116D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)');
        $this->addSql('CREATE INDEX IDX_D79F6B1125F06C53 ON participant (adherent_id)');
        $this->addSql('CREATE INDEX IDX_D79F6B116D861B89 ON participant (equipe_id)');
        $this->addSql('ALTER TABLE planning ADD tatami_id INT DEFAULT NULL, ADD match_tour_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF6320419C2 FOREIGN KEY (tatami_id) REFERENCES tatami (id)');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF6624BD22C FOREIGN KEY (match_tour_id) REFERENCES match_tour (id)');
        $this->addSql('CREATE INDEX IDX_D499BFF6320419C2 ON planning (tatami_id)');
        $this->addSql('CREATE INDEX IDX_D499BFF6624BD22C ON planning (match_tour_id)');
        $this->addSql('ALTER TABLE poule ADD tournoi_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE poule ADD CONSTRAINT FK_FA1FEB40F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id)');
        $this->addSql('CREATE INDEX IDX_FA1FEB40F607770A ON poule (tournoi_id)');
        $this->addSql('ALTER TABLE score ADD participant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_329937519D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id)');
        $this->addSql('CREATE INDEX IDX_329937519D1C3019 ON score (participant_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score_match_tour DROP FOREIGN KEY FK_FA3483A312EB0A51');
        $this->addSql('ALTER TABLE score_match_tour DROP FOREIGN KEY FK_FA3483A3624BD22C');
        $this->addSql('ALTER TABLE tournoi_participant DROP FOREIGN KEY FK_9C531479F607770A');
        $this->addSql('ALTER TABLE tournoi_participant DROP FOREIGN KEY FK_9C5314799D1C3019');
        $this->addSql('DROP TABLE score_match_tour');
        $this->addSql('DROP TABLE tournoi_participant');
        $this->addSql('ALTER TABLE match_tour DROP FOREIGN KEY FK_BBE481B26596FD8');
        $this->addSql('DROP INDEX IDX_BBE481B26596FD8 ON match_tour');
        $this->addSql('ALTER TABLE match_tour DROP poule_id');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B1125F06C53');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B116D861B89');
        $this->addSql('DROP INDEX IDX_D79F6B1125F06C53 ON participant');
        $this->addSql('DROP INDEX IDX_D79F6B116D861B89 ON participant');
        $this->addSql('ALTER TABLE participant DROP adherent_id, DROP equipe_id');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6320419C2');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6624BD22C');
        $this->addSql('DROP INDEX IDX_D499BFF6320419C2 ON planning');
        $this->addSql('DROP INDEX IDX_D499BFF6624BD22C ON planning');
        $this->addSql('ALTER TABLE planning DROP tatami_id, DROP match_tour_id');
        $this->addSql('ALTER TABLE poule DROP FOREIGN KEY FK_FA1FEB40F607770A');
        $this->addSql('DROP INDEX IDX_FA1FEB40F607770A ON poule');
        $this->addSql('ALTER TABLE poule DROP tournoi_id');
        $this->addSql('ALTER TABLE score DROP FOREIGN KEY FK_329937519D1C3019');
        $this->addSql('DROP INDEX IDX_329937519D1C3019 ON score');
        $this->addSql('ALTER TABLE score DROP participant_id');
    }
}
