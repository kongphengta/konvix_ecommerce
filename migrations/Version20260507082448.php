<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507082448 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE seller_earning (id INT AUTO_INCREMENT NOT NULL, seller_id INT NOT NULL, order_id INT NOT NULL, gross_amount DOUBLE PRECISION NOT NULL, commission_amount DOUBLE PRECISION NOT NULL, net_amount DOUBLE PRECISION NOT NULL, commission_rate DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(20) NOT NULL, paid_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6B01A8308DE820D9 (seller_id), INDEX IDX_6B01A8308D9F6D38 (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE seller_earning ADD CONSTRAINT FK_6B01A8308DE820D9 FOREIGN KEY (seller_id) REFERENCES seller (id)');
        $this->addSql('ALTER TABLE seller_earning ADD CONSTRAINT FK_6B01A8308D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE seller ADD iban VARCHAR(34) DEFAULT NULL, ADD commission_rate DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seller_earning DROP FOREIGN KEY FK_6B01A8308DE820D9');
        $this->addSql('ALTER TABLE seller_earning DROP FOREIGN KEY FK_6B01A8308D9F6D38');
        $this->addSql('DROP TABLE seller_earning');
        $this->addSql('ALTER TABLE seller DROP iban, DROP commission_rate');
    }
}
