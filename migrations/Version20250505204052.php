<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250505204052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assignment (id INT AUTO_INCREMENT NOT NULL, caption VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, classes VARCHAR(255) NOT NULL, classes_regexp VARCHAR(255) DEFAULT NULL, school_year INT DEFAULT NULL, public TINYINT(1) NOT NULL, state VARCHAR(255) NOT NULL, soft_deadline DATETIME DEFAULT NULL, hard_deadline DATETIME DEFAULT NULL, owner_id INT NOT NULL, INDEX IDX_30C544BA7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, original_role VARCHAR(255) NOT NULL, original_student_class VARCHAR(16) DEFAULT NULL, effective_role VARCHAR(255) DEFAULT NULL, effective_student_class VARCHAR(16) DEFAULT NULL, restorable_role VARCHAR(255) DEFAULT NULL, last_login_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BA7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assignment DROP FOREIGN KEY FK_30C544BA7E3C61F9');
        $this->addSql('DROP TABLE assignment');
        $this->addSql('DROP TABLE user');
    }
}
