<?php
require_once "BaseModel.php";

class PermitFilesModel extends BaseModel {

    // Adjust to wherever you want uploaded requirement files to actually live.
    private $uploadBaseDir = __DIR__ . '/../uploads/permit_requirements/';

    /**
     * Look up the permit number (act_permit_no) for a given permit id, since
     * filenames are keyed off it instead of the org acronym.
     */
    private function getActivityTitle($permit_id, $campus_type) {
    $table = $campus_type === 'on'
        ? 'activity_permit_oncampus'
        : 'activity_permit_offcampus';

    $stmt = $this->connection->prepare("SELECT activity_title FROM $table WHERE id = ?");
    $stmt->bind_param("i", $permit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['activity_title'] : 'ACTIVITY';
}
    private function getPermitNo($permit_id, $campus_type) {
        $table = $campus_type === 'on'
            ? 'activity_permit_oncampus'
            : 'activity_permit_offcampus';

        $stmt = $this->connection->prepare("SELECT pending_permit_no FROM $table WHERE id = ?");
        $stmt->bind_param("i", $permit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? $row['pending_permit_no'] : 'PERMIT';
    }

    /**
     * Turn a string into a filesystem-safe token.
     * "CIS-PUSO" -> "CIS-PUSO", "IT Society" -> "IT-Society"
     */
    private function sanitizeForFilename($string) {
        $string = trim($string);
        $string = preg_replace('/\s+/', '-', $string);
        $string = preg_replace('/[^A-Za-z0-9\-_]/', '', $string);
        return $string !== '' ? $string : 'ORG';
    }

    private function buildStoredFilename($permitNo, $originalFilename) {
        $ext = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalFilename, PATHINFO_FILENAME);
        $safePermitNo = $this->sanitizeForFilename($permitNo);
        $safeBaseName = $this->sanitizeForFilename($baseName);
        $dateSubmitted = date('Ymd');
        // Added uniqid() to prevent filename collisions for multiple uploads
        $uniqueId = substr(md5(uniqid(rand(), true)), 0, 6);

        return $safePermitNo . '_' . $safeBaseName . '_' . $dateSubmitted . '_' . $uniqueId . ($ext ? ".$ext" : '');
    }

    /**
     * Save one uploaded requirement file: moves it to disk and upserts its
     * metadata row (permit_id + doc_type is a unique key, so resubmitting
     * a doc_type replaces the previous row).
     *
     * $file is a single entry shaped like $_FILES['field'] (name/tmp_name/error...).
     */
    public function saveRequirementFile($permit_id, $acc_id, $campus_type, $doc_type, $doc_label, $file) {
        if (empty($permit_id) || empty($acc_id) || empty($doc_type)) {
            return "missing fields";
        }
        if (!in_array($campus_type, ['on', 'off'], true)) {
            return "invalid campus type";
        }
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return "upload error";
        }

        $table = $campus_type === 'on'
            ? 'permit_requirement_files_oncampus'
            : 'permit_requirement_files_offcampus';

        $activityTitle = $this->getActivityTitle($permit_id, $campus_type);
$storedFilename = $this->buildStoredFilename($activityTitle, $file['name']);

        $targetDir = $this->uploadBaseDir . $campus_type . '/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $targetPath = $targetDir . $storedFilename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return "failed to save file";
        }

        $this->connection->begin_transaction();
        try {
            // Check if this is an 'others' upload or a standard single requirement type
            $isMultipleType = (stripos($doc_type, 'others') === 0);

            if ($isMultipleType) {
                // Straight insert for multiple 'others' files so each file gets its own row
                $stmt = $this->connection->prepare("
                    INSERT INTO $table (permit_id, acc_id, doc_type, doc_label, original_filename, stored_filename)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
            } else {
                // Standard upsert for unique required types (proposal, budget, safety_plan, etc.)
                $stmt = $this->connection->prepare("
                    INSERT INTO $table (permit_id, acc_id, doc_type, doc_label, original_filename, stored_filename)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        doc_label = VALUES(doc_label),
                        original_filename = VALUES(original_filename),
                        stored_filename = VALUES(stored_filename),
                        uploaded_at = CURRENT_TIMESTAMP
                ");
            }

            $stmt->bind_param(
                "iissss",
                $permit_id,
                $acc_id,
                $doc_type,
                $doc_label,
                $file['name'],
                $storedFilename
            );

            $stmt->execute();
            $stmt->close();
            $this->connection->commit();
            return true;
        } catch (Exception $e) {
            $this->connection->rollback();
            @unlink($targetPath);
            error_log("saveRequirementFile failed: " . $e->getMessage());
            return "error: " . $e->getMessage();
        }
    }

    public function saveRequirementFilesBatch($permit_id, $acc_id, $campus_type, array $files) {
        $errors = [];
        foreach ($files as $entry) {
            $result = $this->saveRequirementFile(
                $permit_id,
                $acc_id,
                $campus_type,
                $entry['doc_type'],
                $entry['doc_label'] ?? $entry['doc_type'],
                $entry['file']
            );
            if ($result !== true) {
                $errors[$entry['doc_type']] = $result;
            }
        }
        return empty($errors) ? true : $errors;
    }

    public function getRequirementFilesByPermit($permit_id, $campus_type = 'on') {
        $table = ($campus_type === 'off')
            ? 'permit_requirement_files_offcampus'
            : 'permit_requirement_files_oncampus';

        $stmt = $this->connection->prepare("
            SELECT id, permit_id, doc_type, doc_label, original_filename, stored_filename, uploaded_at
            FROM $table
            WHERE permit_id = ?
            ORDER BY doc_type ASC
        ");
        $stmt->bind_param("i", $permit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    /**
     * Returns true if at least one requirement file row exists for this permit.
     */
    public function hasRequirementFiles($permit_id, $campus_type) {
        if (empty($permit_id) || !in_array($campus_type, ['on', 'off'], true)) {
            return false;
        }

        $table = $campus_type === 'on'
            ? 'permit_requirement_files_oncampus'
            : 'permit_requirement_files_offcampus';

        $stmt = $this->connection->prepare("SELECT 1 FROM $table WHERE permit_id = ? LIMIT 1");
        $stmt->bind_param("i", $permit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /**
     * Batch version: given an array of items with id/db_id and campus_type/campus_type_raw,
     * returns map of permit_id => true/false.
     */
    public function hasRequirementFilesBatch(array $permits) {
        $map = [];
        $onIds = [];
        $offIds = [];

        foreach ($permits as $p) {
            $id = (int) ($p['id'] ?? $p['db_id'] ?? 0);
            $type = $p['campus_type'] ?? $p['campus_type_raw'] ?? '';
            if ($id <= 0) continue;
            $map[$id] = false;
            if ($type === 'on') $onIds[] = $id;
            elseif ($type === 'off') $offIds[] = $id;
        }

        $check = function ($table, $ids) use (&$map) {
            if (empty($ids)) return;
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $stmt = $this->connection->prepare(
                "SELECT DISTINCT permit_id FROM $table WHERE permit_id IN ($placeholders)"
            );
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $map[(int) $row['permit_id']] = true;
            }
            $stmt->close();
        };

        $check('permit_requirement_files_oncampus', $onIds);
        $check('permit_requirement_files_offcampus', $offIds);

        return $map;
    }

    public function getRequirementFileById($id, $campus_type) {
        $table = $campus_type === 'on'
            ? 'permit_requirement_files_oncampus'
            : 'permit_requirement_files_offcampus';

        $stmt = $this->connection->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function deleteRequirementFile($id, $campus_type) {
        $table = $campus_type === 'on'
            ? 'permit_requirement_files_oncampus'
            : 'permit_requirement_files_offcampus';

        $file = $this->getRequirementFileById($id, $campus_type);
        if (!$file) {
            return "not found";
        }

        $stmt = $this->connection->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            $path = $this->uploadBaseDir . $campus_type . '/' . $file['stored_filename'];
            @unlink($path);
        }
        return $result ? true : "delete failed";
    }
}