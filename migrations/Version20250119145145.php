<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250119145145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE videos (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, youtube_id VARCHAR(255) NOT NULL, data CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('DROP TABLE video');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE video (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, youtube_id VARCHAR(255) NOT NULL COLLATE "BINARY", data CLOB DEFAULT NULL COLLATE "BINARY", created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('DROP TABLE videos');
    }
}
