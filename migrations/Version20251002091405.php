<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251002091405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande ADD ship_full_name VARCHAR(255) DEFAULT NULL, ADD ship_address VARCHAR(255) DEFAULT NULL, ADD ship_zip VARCHAR(32) DEFAULT NULL, ADD ship_city VARCHAR(128) DEFAULT NULL, ADD ship_country VARCHAR(2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP ship_full_name, DROP ship_address, DROP ship_zip, DROP ship_city, DROP ship_country');
    }
}
