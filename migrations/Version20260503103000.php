<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add product condition field (new/used)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE product ADD product_condition VARCHAR(20) NOT NULL DEFAULT 'new'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP product_condition');
    }
}
