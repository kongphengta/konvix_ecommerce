<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209105845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE seller_request (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, shop_name VARCHAR(255) NOT NULL, shop_description LONGTEXT DEFAULT NULL, contact_email VARCHAR(255) NOT NULL, contact_phone VARCHAR(50) DEFAULT NULL, INDEX IDX_E099368EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE seller_request ADD CONSTRAINT FK_E099368EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seller_request DROP FOREIGN KEY FK_E099368EA76ED395');
        $this->addSql('DROP TABLE seller_request');
    }
}
