<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260420115110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE care_action (id INT AUTO_INCREMENT NOT NULL, action_type VARCHAR(255) NOT NULL, stat_delta INT NOT NULL, xp_earned INT NOT NULL, performed_at DATETIME NOT NULL, pet_id INT NOT NULL, performer_id INT NOT NULL, INDEX IDX_F959DF78966F7FB6 (pet_id), INDEX IDX_F959DF786C6B33F3 (performer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE delegation (id INT AUTO_INCREMENT NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, status VARCHAR(255) NOT NULL, pet_id INT NOT NULL, owner_id INT NOT NULL, caretaker_id INT NOT NULL, INDEX IDX_292F436D966F7FB6 (pet_id), INDEX IDX_292F436D7E3C61F9 (owner_id), INDEX IDX_292F436D3F070B8B (caretaker_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE inventory (id INT AUTO_INCREMENT NOT NULL, items JSON NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_B12D4A36A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE item (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, effect VARCHAR(255) NOT NULL, effect_value INT NOT NULL, description VARCHAR(500) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE pet (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, species VARCHAR(255) NOT NULL, level INT NOT NULL, xp INT NOT NULL, xp_to_next_level INT NOT NULL, hunger INT NOT NULL, happiness INT NOT NULL, health INT NOT NULL, energy INT NOT NULL, last_interacted_at DATETIME NOT NULL, born_at DATETIME NOT NULL, is_alive TINYINT NOT NULL, owner_id INT NOT NULL, INDEX IDX_E4529B857E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE care_action ADD CONSTRAINT FK_F959DF78966F7FB6 FOREIGN KEY (pet_id) REFERENCES pet (id)');
        $this->addSql('ALTER TABLE care_action ADD CONSTRAINT FK_F959DF786C6B33F3 FOREIGN KEY (performer_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE delegation ADD CONSTRAINT FK_292F436D966F7FB6 FOREIGN KEY (pet_id) REFERENCES pet (id)');
        $this->addSql('ALTER TABLE delegation ADD CONSTRAINT FK_292F436D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE delegation ADD CONSTRAINT FK_292F436D3F070B8B FOREIGN KEY (caretaker_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE inventory ADD CONSTRAINT FK_B12D4A36A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE pet ADD CONSTRAINT FK_E4529B857E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE care_action DROP FOREIGN KEY FK_F959DF78966F7FB6');
        $this->addSql('ALTER TABLE care_action DROP FOREIGN KEY FK_F959DF786C6B33F3');
        $this->addSql('ALTER TABLE delegation DROP FOREIGN KEY FK_292F436D966F7FB6');
        $this->addSql('ALTER TABLE delegation DROP FOREIGN KEY FK_292F436D7E3C61F9');
        $this->addSql('ALTER TABLE delegation DROP FOREIGN KEY FK_292F436D3F070B8B');
        $this->addSql('ALTER TABLE inventory DROP FOREIGN KEY FK_B12D4A36A76ED395');
        $this->addSql('ALTER TABLE pet DROP FOREIGN KEY FK_E4529B857E3C61F9');
        $this->addSql('DROP TABLE care_action');
        $this->addSql('DROP TABLE delegation');
        $this->addSql('DROP TABLE inventory');
        $this->addSql('DROP TABLE item');
        $this->addSql('DROP TABLE pet');
        $this->addSql('DROP TABLE `user`');
    }
}
