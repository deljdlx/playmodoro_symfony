<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250119150345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__videos AS SELECT id, youtube_id, data, created_at, updated_at FROM videos');
        $this->addSql('DROP TABLE videos');
        $this->addSql('CREATE TABLE videos (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, api_id VARCHAR(255) NOT NULL, data CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, source VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO videos (id, api_id, data, created_at, updated_at) SELECT id, youtube_id, data, created_at, updated_at FROM __temp__videos');
        $this->addSql('DROP TABLE __temp__videos');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__videos AS SELECT id, data, created_at, updated_at FROM videos');
        $this->addSql('DROP TABLE videos');
        $this->addSql('CREATE TABLE videos (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, data CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, youtube_id VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO videos (id, data, created_at, updated_at) SELECT id, data, created_at, updated_at FROM __temp__videos');
        $this->addSql('DROP TABLE __temp__videos');
    }
}
