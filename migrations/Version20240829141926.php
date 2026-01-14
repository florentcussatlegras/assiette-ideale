<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240829141926 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_forbidden_foods DROP CONSTRAINT fk_d3226624a76ed395');
        $this->addSql('ALTER TABLE user_diets DROP CONSTRAINT fk_c23ff0fee1e13ace');
        $this->addSql('ALTER TABLE user_diets DROP CONSTRAINT fk_c23ff0fea76ed395');
        $this->addSql('DROP TABLE user_forbidden_foods');
        $this->addSql('DROP TABLE user_diets');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE user_forbidden_foods (user_id INT NOT NULL, food_id INT NOT NULL, PRIMARY KEY(user_id, food_id))');
        $this->addSql('CREATE INDEX idx_d3226624ba8e87c4 ON user_forbidden_foods (food_id)');
        $this->addSql('CREATE INDEX idx_d3226624a76ed395 ON user_forbidden_foods (user_id)');
        $this->addSql('CREATE TABLE user_diets (user_id INT NOT NULL, diet_id INT NOT NULL, PRIMARY KEY(user_id, diet_id))');
        $this->addSql('CREATE INDEX idx_c23ff0fee1e13ace ON user_diets (diet_id)');
        $this->addSql('CREATE INDEX idx_c23ff0fea76ed395 ON user_diets (user_id)');
        $this->addSql('ALTER TABLE user_forbidden_foods ADD CONSTRAINT fk_d3226624a76ed395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_diets ADD CONSTRAINT fk_c23ff0fee1e13ace FOREIGN KEY (diet_id) REFERENCES diet (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_diets ADD CONSTRAINT fk_c23ff0fea76ed395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
