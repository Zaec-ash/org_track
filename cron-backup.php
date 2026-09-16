<?php
// cron-backup.php — hit by cron-job.org on a schedule

$secret = 'PUT-A-LONG-RANDOM-STRING-HERE'; // change this

if (!isset($_GET['key']) || !hash_equals($secret, $_GET['key'])) {
    http_response_code(403);
    die('Forbidden');
}

require_once __DIR__ . '/models/DatabaseBackup.php';

try {
    $backup = new DatabaseBackup(__DIR__ . '/backups', 8);
    $file = $backup->run();
    echo "OK: " . basename($file);
} catch (Exception $e) {
    http_response_code(500);
    echo "Backup failed: " . $e->getMessage();
}