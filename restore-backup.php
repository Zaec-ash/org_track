<?php
// restore-backup.php — manual restore tool, for capstone defense / emergencies only

$secret = 'PUT-A-DIFFERENT-LONG-RANDOM-STRING-HERE'; // different from the backup key

if (!isset($_GET['key']) || !hash_equals($secret, $_GET['key'])) {
    http_response_code(403);
    die('Forbidden');
}

require_once __DIR__ . '/models/DatabaseRestore.php';

$restore = new DatabaseRestore(__DIR__ . '/backups');
$backups = $restore->listBackups();

$message = '';

// step 2: actual restore, only after explicit confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    try {
        $restore->restore($_POST['filename']);
        $message = "✅ Restored successfully from " . htmlspecialchars($_POST['filename']);
    } catch (Exception $e) {
        $message = "❌ " . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Database Restore</title></head>
<body style="font-family: sans-serif; max-width: 600px; margin: 40px auto;">
    <h2>Database Restore</h2>

    <?php if ($message): ?>
        <p><strong><?= $message ?></strong></p>
    <?php endif; ?>

    <p style="color: red;"><strong>Warning:</strong> Restoring will overwrite existing data in tables present in the backup file. This cannot be undone.</p>

    <form method="POST" onsubmit="return confirm('Are you sure? This will overwrite current data.');">
        <label for="filename">Select backup:</label><br>
        <select name="filename" id="filename" required>
            <?php foreach ($backups as $b): ?>
                <option value="<?= htmlspecialchars($b['filename']) ?>">
                    <?= htmlspecialchars($b['filename']) ?> (<?= round($b['size'] / 1024, 1) ?> KB, <?= $b['modified'] ?>)
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <input type="hidden" name="confirm" value="yes">
        <button type="submit">Restore This Backup</button>
    </form>
</body>
</html>