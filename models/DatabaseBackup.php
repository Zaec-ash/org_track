<?php

class DatabaseBackup
{
    private $connection;
    private $backupDir;
    private $keep;

    public function __construct($backupDir, $keep = 8)
    {
        // Credentials live in config.php (gitignored, never shared).
        $config = require __DIR__ . '/../config.php';

        $this->connection = new mysqli(
            $config['host'],
            $config['user'],
            $config['password'],
            $config['dbname']
        );

        if ($this->connection->connect_error) {
            die("Backup DB connection failed: " . $this->connection->connect_error);
        }

        $this->backupDir = rtrim($backupDir, '/');
        $this->keep = $keep;

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function run()
    {
        $filename = $this->backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
        $this->dump($filename);
        $this->cleanOldBackups();
        return $filename;
    }

    private function dump($filename)
    {
        $mysqli = $this->connection;
        $output = "-- Backup generated on " . date('Y-m-d H:i:s') . "\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tablesResult = $mysqli->query('SHOW TABLES');
        while ($row = $tablesResult->fetch_row()) {
            $table = $row[0];

            // structure
            $createResult = $mysqli->query("SHOW CREATE TABLE `$table`");
            $createRow = $createResult->fetch_assoc();
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $output .= $createRow['Create Table'] . ";\n\n";

            // data
            $dataResult = $mysqli->query("SELECT * FROM `$table`");
            while ($dataRow = $dataResult->fetch_assoc()) {
                $values = array_map(function ($val) use ($mysqli) {
                    if ($val === null) return 'NULL';
                    return "'" . $mysqli->real_escape_string($val) . "'";
                }, array_values($dataRow));

                $columns = '`' . implode('`, `', array_keys($dataRow)) . '`';
                $output .= "INSERT INTO `$table` ($columns) VALUES (" . implode(', ', $values) . ");\n";
            }
            $output .= "\n";
        }

        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

        file_put_contents($filename, $output);
    }

    private function cleanOldBackups()
    {
        $files = glob($this->backupDir . '/backup_*.sql');
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        foreach (array_slice($files, $this->keep) as $old) {
            unlink($old);
        }
    }
}