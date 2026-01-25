<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251203180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs frais_livraison (float) et transporteur (string) à la table order';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD frais_livraison DOUBLE PRECISION DEFAULT NULL, ADD transporteur VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` DROP frais_livraison, DROP transporteur');
    }
}
