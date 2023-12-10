<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231210140746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commandes ADD adresse_livraison_id INT NOT NULL');
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT FK_35D4282CBE2F0A35 FOREIGN KEY (adresse_livraison_id) REFERENCES utilisateurs_adresses (id)');
        $this->addSql('CREATE INDEX IDX_35D4282CBE2F0A35 ON commandes (adresse_livraison_id)');
        $this->addSql('ALTER TABLE media CHANGE path path VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY FK_35D4282CBE2F0A35');
        $this->addSql('DROP INDEX IDX_35D4282CBE2F0A35 ON commandes');
        $this->addSql('ALTER TABLE commandes DROP adresse_livraison_id');
        $this->addSql('ALTER TABLE media CHANGE path path TEXT NOT NULL');
    }
}
