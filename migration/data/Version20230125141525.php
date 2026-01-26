<?php

declare(strict_types=1);

namespace OxidEsales\MediaLibrary\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230125141525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update module tables';
    }

    public function up(Schema $schema): void
    {
        // Check columns using direct SQL instead of schema introspection
        // to avoid DBAL 4.0 issues with ENUM columns in other tables
        $columns = $this->connection->executeQuery(
            "SHOW COLUMNS FROM `ddmedia`"
        )->fetchAllAssociative();

        $columnNames = array_column($columns, 'Field');

        if (!in_array('DDIMAGESIZE', $columnNames)) {
            $this->addSql('ALTER TABLE `ddmedia` ADD `DDIMAGESIZE` VARCHAR(100) AFTER `DDTHUMB`;');
        }

        if (!in_array('OXSHOPID', $columnNames)) {
            $this->addSql('ALTER TABLE `ddmedia` ADD `OXSHOPID` INT(10) UNSIGNED NOT NULL AFTER `OXID`;');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
