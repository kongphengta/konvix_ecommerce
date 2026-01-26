<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260126073715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE code_promo_usage (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, code_promo_id INT NOT NULL, used_at DATETIME NOT NULL, INDEX IDX_70838B56A76ED395 (user_id), INDEX IDX_70838B56294102D4 (code_promo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE code_promo_usage ADD CONSTRAINT FK_70838B56A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE code_promo_usage ADD CONSTRAINT FK_70838B56294102D4 FOREIGN KEY (code_promo_id) REFERENCES code_promo (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE code_promo_usage DROP FOREIGN KEY FK_70838B56A76ED395');
        $this->addSql('ALTER TABLE code_promo_usage DROP FOREIGN KEY FK_70838B56294102D4');
        $this->addSql('DROP TABLE code_promo_usage');
    }
}
