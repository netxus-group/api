<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeUtf8mb4AndAssetUrls extends Migration
{
    public function up()
    {
        $db = $this->db;
        $dbName = $db->database;

        if ($dbName !== '') {
            $db->query("ALTER DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        $tables = $db->query('SHOW TABLES')->getResultArray();
        foreach ($tables as $row) {
            $tableName = array_values($row)[0] ?? null;
            if (!is_string($tableName) || $tableName === '') {
                continue;
            }

            $db->query("ALTER TABLE `{$tableName}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        $textColumns = $db->query(
            "SELECT TABLE_NAME, COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND DATA_TYPE IN ('char', 'varchar', 'text', 'tinytext', 'mediumtext', 'longtext')",
            [$dbName],
        )->getResultArray();

        foreach ($textColumns as $column) {
            $table = $column['TABLE_NAME'] ?? null;
            $field = $column['COLUMN_NAME'] ?? null;
            if (!is_string($table) || !is_string($field) || $table === '' || $field === '') {
                continue;
            }

            $db->query(
                "UPDATE `{$table}`
                 SET `{$field}` = CONVERT(CAST(CONVERT(`{$field}` USING latin1) AS BINARY) USING utf8mb4)
                 WHERE `{$field}` IS NOT NULL
                   AND (
                     INSTR(`{$field}`, 'Ã') > 0
                     OR INSTR(`{$field}`, 'Â') > 0
                     OR INSTR(`{$field}`, 'â') > 0
                   )"
            );
        }

        if ($db->fieldExists('cover_image_url', 'news')) {
            $db->query(
                "UPDATE `news`
                 SET `cover_image_url` = CASE
                   WHEN `cover_image_url` IS NULL OR TRIM(`cover_image_url`) = '' THEN `cover_image_url`
                   WHEN `cover_image_url` LIKE 'writable/uploads/%' THEN CONCAT('/uploads/', SUBSTRING(`cover_image_url`, 18))
                   WHEN `cover_image_url` LIKE '/writable/uploads/%' THEN CONCAT('/uploads/', SUBSTRING(`cover_image_url`, 19))
                   WHEN `cover_image_url` LIKE 'uploads/%' THEN CONCAT('/uploads/', SUBSTRING(`cover_image_url`, 9))
                   WHEN (`cover_image_url` LIKE 'http://localhost%' OR `cover_image_url` LIKE 'https://localhost%' OR `cover_image_url` LIKE 'http://127.0.0.1%' OR `cover_image_url` LIKE 'https://127.0.0.1%')
                        AND LOCATE('/uploads/', `cover_image_url`) > 0
                     THEN SUBSTRING(`cover_image_url`, LOCATE('/uploads/', `cover_image_url`))
                   ELSE `cover_image_url`
                 END"
            );
        }

        foreach (['url', 'public_url'] as $columnName) {
            if (!$db->fieldExists($columnName, 'media_images')) {
                continue;
            }

            $db->query(
                "UPDATE `media_images`
                 SET `{$columnName}` = CASE
                   WHEN `{$columnName}` IS NULL OR TRIM(`{$columnName}`) = '' THEN `{$columnName}`
                   WHEN `{$columnName}` LIKE 'writable/uploads/%' THEN CONCAT('/uploads/', SUBSTRING(`{$columnName}`, 18))
                   WHEN `{$columnName}` LIKE '/writable/uploads/%' THEN CONCAT('/uploads/', SUBSTRING(`{$columnName}`, 19))
                   WHEN `{$columnName}` LIKE 'uploads/%' THEN CONCAT('/uploads/', SUBSTRING(`{$columnName}`, 9))
                   WHEN (`{$columnName}` LIKE 'http://localhost%' OR `{$columnName}` LIKE 'https://localhost%' OR `{$columnName}` LIKE 'http://127.0.0.1%' OR `{$columnName}` LIKE 'https://127.0.0.1%')
                        AND LOCATE('/uploads/', `{$columnName}`) > 0
                     THEN SUBSTRING(`{$columnName}`, LOCATE('/uploads/', `{$columnName}`))
                   ELSE `{$columnName}`
                 END"
            );
        }

        if ($db->fieldExists('content', 'ad_slots')) {
            $db->query(
                "UPDATE `ad_slots`
                 SET `content` = JSON_SET(
                   `content`,
                   '$.imageUrl',
                   CASE
                     WHEN JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl')) IS NULL THEN JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl'))
                     WHEN JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl')) LIKE 'writable/uploads/%' THEN CONCAT('/uploads/', SUBSTRING(JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl')), 18))
                     WHEN JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl')) LIKE '/writable/uploads/%' THEN CONCAT('/uploads/', SUBSTRING(JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl')), 19))
                     WHEN JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl')) LIKE 'uploads/%' THEN CONCAT('/uploads/', SUBSTRING(JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl')), 9))
                     WHEN (
                       JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl')) LIKE 'http://localhost%'
                       OR JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl')) LIKE 'https://localhost%'
                       OR JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl')) LIKE 'http://127.0.0.1%'
                       OR JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl')) LIKE 'https://127.0.0.1%'
                     ) AND LOCATE('/uploads/', JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl'))) > 0
                       THEN SUBSTRING(
                         JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl')),
                         LOCATE('/uploads/', JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl')))
                       )
                     ELSE JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.imageUrl'))
                   END
                 )
                 WHERE JSON_VALID(`content`)
                   AND JSON_EXTRACT(`content`, '$.imageUrl') IS NOT NULL"
            );
        }
    }

    public function down()
    {
        // no-op
    }
}
