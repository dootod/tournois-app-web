<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user table with roles and api token, linked OneToOne to adherent';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `user` (
            id INT AUTO_INCREMENT NOT NULL,
            adherent_id INT DEFAULT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            api_token VARCHAR(64) DEFAULT NULL,
            UNIQUE INDEX UNIQ_USER_EMAIL (email),
            UNIQUE INDEX UNIQ_USER_API_TOKEN (api_token),
            UNIQUE INDEX UNIQ_USER_ADHERENT (adherent_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_USER_ADHERENT FOREIGN KEY (adherent_id) REFERENCES adherent (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `user`');
    }
}
