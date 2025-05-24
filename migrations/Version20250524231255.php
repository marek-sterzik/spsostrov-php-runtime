<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250524231255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assignment (id INT AUTO_INCREMENT NOT NULL, caption VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, classes VARCHAR(255) NOT NULL, classes_regexp VARCHAR(255) DEFAULT NULL, school_year INT DEFAULT NULL, public TINYINT(1) NOT NULL, submission_mode VARCHAR(16) NOT NULL, backed_up TINYINT(1) NOT NULL, missed_draft_policy VARCHAR(16) NOT NULL, state VARCHAR(16) NOT NULL, soft_deadline DATETIME DEFAULT NULL, hard_deadline DATETIME DEFAULT NULL, main_order INT NOT NULL, created_at DATETIME NOT NULL, activated_at DATETIME DEFAULT NULL, deactivated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, INDEX IDX_30C544BA7E3C61F9 (owner_id), INDEX main_order_created_at_index (main_order, created_at), INDEX state_hard_deadline_index (state, hard_deadline), INDEX school_year_state (school_year, state), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE submission (id INT AUTO_INCREMENT NOT NULL, uuid CHAR(36) NOT NULL, state VARCHAR(16) NOT NULL, is_current TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, submitted_at DATETIME DEFAULT NULL, assignment_id INT NOT NULL, submitter_id INT NOT NULL, UNIQUE INDEX UNIQ_DB055AF3D17F50A6 (uuid), INDEX IDX_DB055AF3D19302F8 (assignment_id), INDEX IDX_DB055AF3919E5513 (submitter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, guessed_surname VARCHAR(255) DEFAULT NULL, original_role VARCHAR(255) NOT NULL, original_student_class VARCHAR(16) DEFAULT NULL, effective_role VARCHAR(255) DEFAULT NULL, effective_student_class VARCHAR(16) DEFAULT NULL, restorable_role VARCHAR(255) DEFAULT NULL, last_login_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), INDEX guessed_surname_name_index (guessed_surname, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BA7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF3D19302F8 FOREIGN KEY (assignment_id) REFERENCES assignment (id)');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF3919E5513 FOREIGN KEY (submitter_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assignment DROP FOREIGN KEY FK_30C544BA7E3C61F9');
        $this->addSql('ALTER TABLE submission DROP FOREIGN KEY FK_DB055AF3D19302F8');
        $this->addSql('ALTER TABLE submission DROP FOREIGN KEY FK_DB055AF3919E5513');
        $this->addSql('DROP TABLE assignment');
        $this->addSql('DROP TABLE submission');
        $this->addSql('DROP TABLE user');
    }
}
