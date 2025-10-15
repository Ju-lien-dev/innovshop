<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251001084250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Assainit les données (supprime commandes sans user), passe user_id en NOT NULL et recrée la FK proprement.';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        $db   = $conn->fetchOne('SELECT DATABASE()');

        // 1) Drop toute FK existante sur commande.user_id (nom inconnu)
        $fkNames = $conn->fetchFirstColumn(
            <<<SQL
            SELECT k.CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE k
            WHERE k.TABLE_SCHEMA = :db
              AND k.TABLE_NAME   = 'commande'
              AND k.COLUMN_NAME  = 'user_id'
              AND k.REFERENCED_TABLE_NAME IS NOT NULL
            SQL,
            ['db' => $db]
        );
        foreach ($fkNames as $fk) {
            $this->addSql(sprintf('ALTER TABLE commande DROP FOREIGN KEY `%s`', $fk));
        }

        // 2) Nettoyage des données incohérentes pour pouvoir passer NOT NULL
        //    (tu as dit: "le client doit être connecté pour commander" -> on supprime ces enregistrements de test)
        $this->addSql(<<<SQL
            DELETE ac FROM article_commande ac
            INNER JOIN commande c ON c.id = ac.commande_id
            WHERE c.user_id IS NULL
        SQL);
        $this->addSql('DELETE FROM commande WHERE user_id IS NULL');

        // 3) user_id NOT NULL
        $this->addSql('ALTER TABLE commande MODIFY user_id INT NOT NULL');

        // 4) Recrée une FK propre si absente
        $exists = (int) $conn->fetchOne(
            <<<SQL
            SELECT COUNT(*)
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = :db
              AND TABLE_NAME = 'commande'
              AND CONSTRAINT_NAME = 'FK_COMMANDE_USER'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            SQL,
            ['db' => $db]
        );
        if ($exists === 0) {
            $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_COMMANDE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE RESTRICT');
        }
    }

    public function down(Schema $schema): void
    {
        $conn = $this->connection;
        $db   = $conn->fetchOne('SELECT DATABASE()');

        // Drop la FK si présente
        $exists = (int) $conn->fetchOne(
            <<<SQL
            SELECT COUNT(*)
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = :db
              AND TABLE_NAME = 'commande'
              AND CONSTRAINT_NAME = 'FK_COMMANDE_USER'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            SQL,
            ['db' => $db]
        );
        if ($exists > 0) {
            $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_COMMANDE_USER');
        }

        // Revenir en nullable
        $this->addSql('ALTER TABLE commande MODIFY user_id INT DEFAULT NULL');
    }
}
