<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251001083231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'No-op: neutralise l’ancienne migration cassée';
    }

    public function up(Schema $schema): void
    {
        // Intentionnellement vide pour marquer la migration comme exécutée sans action.
    }

    public function down(Schema $schema): void
    {
        // Intentionnellement vide.
    }
}
