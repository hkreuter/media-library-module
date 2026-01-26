<?php

declare(strict_types=1);

namespace OxidEsales\MediaLibrary\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230203134229 extends AbstractMigration
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

        if (!in_array('DDFOLDERID', $columnNames)) {
            $this->addSql(
                "ALTER TABLE `ddmedia` ADD `DDFOLDERID` CHAR(32) NOT NULL DEFAULT '' AFTER `DDIMAGESIZE`"
            );
        }
    }

    public function down(Schema $schema): void
    {
    }
}
