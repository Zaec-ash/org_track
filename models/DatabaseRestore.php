<?php

class DatabaseRestore
{
    private $connection;
    private $backupDir;

    public function __construct($backupDir)
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
            die("Restore DB connection failed: " . $this->connection->connect_error);
        }

        $this->backupDir = rtrim($backupDir, '/');
    }

    /**
     * List available backup files (newest first) for a picker UI
     */
    public function listBackups()
    {
        $files = glob($this->backupDir . '/backup_*.sql');
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        return array_map(function ($f) {
            return [
                'filename' => basename($f),
                'size'     => filesize($f),
                'modified' => date('Y-m-d H:i:s', filemtime($f)),
            ];
        }, $files);
    }

    /**
     * Restore from a specific backup filename (must already exist in backupDir)
     */
    public function restore($filename)
    {
        // prevent path traversal — only allow bare filenames from this exact folder
        $filename = basename($filename);
        $filepath = $this->backupDir . '/' . $filename;

        if (!file_exists($filepath)) {
            throw new Exception("Backup file not found: $filename");
        }

        $sql = file_get_contents($filepath);
        if ($sql === false || trim($sql) === '') {
            throw new Exception("Backup file is empty or unreadable: $filename");
        }

        // mysqli_multi_query runs the whole dump (multiple statements) in one go
        if (!$this->connection->multi_query($sql)) {
            throw new Exception("Restore failed: " . $this->connection->error);
        }

        // must flush through all result sets from multi_query before connection is usable again
        do {
            if ($result = $this->connection->store_result()) {
                $result->free();
            }
        } while ($this->connection->more_results() && $this->connection->next_result());

        if ($this->connection->errno) {
            throw new Exception("Restore completed with errors: " . $this->connection->error);
        }

        return true;
    }
}