<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251201190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ téléphone à la table user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD phone VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP phone');
    }
}
