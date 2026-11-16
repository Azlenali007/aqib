<?php
declare(strict_types=1);

class SettingsService
{
    public static function createSqlBackup(): array
    {
        $dir = SMM_STORAGE . '/backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $filename = 'backup_' . date('Ymd_His') . '.sql';
        $path = $dir . '/' . $filename;
        $pdo = Database::pdo();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        $fh = fopen($path, 'w');
        if (!$fh) {
            return ['success' => false, 'error' => 'Cannot write backup file in storage/backups/.'];
        }
        fwrite($fh, "-- SMM Panel Backup generated " . date('Y-m-d H:i:s') . "\nSET FOREIGN_KEY_CHECKS=0;\n\n");
        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            fwrite($fh, "DROP TABLE IF EXISTS `$table`;\n" . $create['Create Table'] . ";\n\n");
            $stmt = $pdo->query("SELECT * FROM `$table`");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cols = '`' . implode('`,`', array_keys($row)) . '`';
                $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($row));
                fwrite($fh, "INSERT INTO `$table` ($cols) VALUES (" . implode(',', $vals) . ");\n");
            }
            fwrite($fh, "\n");
        }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
        return ['success' => true, 'filename' => $filename, 'path' => $path];
    }
}
