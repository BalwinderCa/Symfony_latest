<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241226110818 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE category_table CHANGE media_id media_id INT NOT NULL');
        $this->addSql('DROP INDEX UNIQ_C3D4D4BD92FC23A8 ON fos_user_table');
        $this->addSql('DROP INDEX UNIQ_C3D4D4BDA0D96FBF ON fos_user_table');
        $this->addSql('ALTER TABLE fos_user_table DROP username, DROP username_canonical, DROP email_canonical, DROP enabled, DROP salt, DROP last_login, DROP locked, DROP expired, DROP expires_at, DROP confirmation_token, DROP password_requested_at, DROP credentials_expired, DROP credentials_expire_at, CHANGE email email VARCHAR(180) NOT NULL, CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE is_verified is_verified TINYINT(1) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C3D4D4BDE7927C74 ON fos_user_table (email)');
        $this->addSql('ALTER TABLE language_table CHANGE media_id media_id INT NOT NULL');
        $this->addSql('ALTER TABLE media_table CHANGE date date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE slide_table CHANGE media_id media_id INT NOT NULL');
        $this->addSql('ALTER TABLE status_table CHANGE title title LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE transaction_table CHANGE user_id user_id INT NOT NULL, CHANGE invited_id invited_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE category_table CHANGE media_id media_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_C3D4D4BDE7927C74 ON fos_user_table');
        $this->addSql('ALTER TABLE fos_user_table ADD username VARCHAR(255) NOT NULL, ADD username_canonical VARCHAR(255) NOT NULL, ADD email_canonical VARCHAR(255) NOT NULL, ADD enabled TINYINT(1) NOT NULL, ADD salt VARCHAR(255) NOT NULL, ADD last_login DATETIME DEFAULT NULL, ADD locked TINYINT(1) NOT NULL, ADD expired TINYINT(1) NOT NULL, ADD expires_at DATETIME DEFAULT NULL, ADD confirmation_token VARCHAR(255) DEFAULT NULL, ADD password_requested_at DATETIME DEFAULT NULL, ADD credentials_expired TINYINT(1) NOT NULL, ADD credentials_expire_at DATETIME DEFAULT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE roles roles JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE is_verified is_verified TINYINT(1) DEFAULT 0');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C3D4D4BD92FC23A8 ON fos_user_table (username_canonical)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C3D4D4BDA0D96FBF ON fos_user_table (email_canonical)');
        $this->addSql('ALTER TABLE language_table CHANGE media_id media_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE media_table CHANGE date date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE slide_table CHANGE media_id media_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE status_table CHANGE title title LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`');
        $this->addSql('ALTER TABLE transaction_table CHANGE user_id user_id INT DEFAULT NULL, CHANGE invited_id invited_id INT DEFAULT NULL');
    }
}
