<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251001083709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'No-op: neutralise une ancienne migration qui force NOT NULL sans nettoyage';
    }

    public function up(Schema $schema): void
    {
        // Intentionnellement vide
    }

    public function down(Schema $schema): void
    {
        // Intentionnellement vide
    }
}
