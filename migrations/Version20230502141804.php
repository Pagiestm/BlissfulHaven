<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230502141804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stocks DROP FOREIGN KEY FK_56F79805CD11A2CF');
        $this->addSql('DROP INDEX IDX_56F79805CD11A2CF ON stocks');
        $this->addSql('ALTER TABLE stocks DROP produits_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stocks ADD produits_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE stocks ADD CONSTRAINT FK_56F79805CD11A2CF FOREIGN KEY (produits_id) REFERENCES produits (id)');
        $this->addSql('CREATE INDEX IDX_56F79805CD11A2CF ON stocks (produits_id)');
    }
}
