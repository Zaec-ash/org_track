<?php
require_once "BaseModel.php";

/**
 * Everything about a permit BEFORE/AROUND submission and requirements —
 * creating an application, listing an org's applications, updating
 * requirement status, and rejecting requirements. Approval itself and
 * anything about already-approved permits lives in ApprovedPermitModel;
 * accomplishment reports live in AccomplishmentReportModel.
 */
class PermitApplicationModel extends BaseModel {

    /**
     * Calculates the next continuous sequence number across both on-campus
     * and off-campus tables, starting at 0100 (floor set to 99).
     */
    private function getNextPermitSequence() {
        $result = $this->connection->query("
            SELECT MAX(seq) AS max_seq FROM (
                SELECT CAST(SUBSTRING_INDEX(pending_permit_no, '-', -1) AS UNSIGNED) AS seq
                FROM activity_permit_oncampus
                WHERE pending_permit_no != ''
                UNION ALL
                SELECT CAST(SUBSTRING_INDEX(pending_permit_no, '-', -1) AS UNSIGNED) AS seq
                FROM activity_permit_offcampus
                WHERE pending_permit_no != ''
            ) combined
        ");
        $row = $result ? $result->fetch_assoc() : null;
        $maxSeq = $row && $row['max_seq'] !== null ? (int)$row['max_seq'] : 0;
        return $maxSeq + 1;
    }

    public function insertPermit($input) {
        if (empty($input['acc_id'])) {
            return "not logged in";
        }
        if (empty($input['act_title']) || empty($input['campus_type'])) {
            return "missing fields";
        }

        $act_type = $input['act_type'] ?? '';
        if ($act_type === 'Others' && !empty($input['other_text'])) {
            $act_type = $input['other_text'];
        }

        $campus_type = $input['campus_type'];

        $this->connection->begin_transaction();
        try {
            if ($campus_type === 'on') {
                $stmt = $this->connection->prepare("
                    INSERT INTO activity_permit_oncampus
                        (pending_permit_no, activity_title, nature_of_activity, start_date, end_date, time_start, time_end, venue, adviser, acc_id,requirements_status)
                    VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?,?)
                ");
                // bind_param() requires every argument to be passed by reference,
                // so a literal like 'pending' can't be passed directly — assign it
                // to a variable first.
                $requirements_status = 'pending';
                $stmt->bind_param(
                    "ssssssssis",
                    $input['act_title'],
                    $act_type,
                    $input['act_start'],
                    $input['act_end'],
                    $input['time_start'],
                    $input['time_end'],
                    $input['act_venue'],
                    $input['adviser_name'],
                    $input['acc_id'],
                    $requirements_status
                );
                $stmt->execute();
                $insert_id = $stmt->insert_id;
                $stmt->close();
                $table = 'activity_permit_oncampus';

            } elseif ($campus_type === 'off') {
                if (empty($input['off_campus_address']) || empty($input['safety_plan'])) {
                    $this->connection->rollback();
                    return "missing fields";
                }
                $transportation = $input['transportation'] ?? null;
                $parent_consent = $input['parent_consent'] ?? 'no';
                $requirements_status = 'pending';

                $stmt = $this->connection->prepare("
                    INSERT INTO activity_permit_offcampus
                        (pending_permit_no, activity_title, nature_of_activity, start_date, end_date, time_start, time_end, venue, adviser, off_campus_address, transportation, safety_plan, parent_consent, acc_id,requirements_status)
                    VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "ssssssssssssis",
                    $input['act_title'],
                    $act_type,
                    $input['act_start'],
                    $input['act_end'],
                    $input['time_start'],
                    $input['time_end'],
                    $input['act_venue'],
                    $input['adviser_name'],
                    $input['off_campus_address'],
                    $transportation,
                    $input['safety_plan'],
                    $parent_consent,
                    $input['acc_id'],
                    $requirements_status
                );
                $stmt->execute();
                $insert_id = $stmt->insert_id;
                $stmt->close();
                $table = 'activity_permit_offcampus';

            } else {
                $this->connection->rollback();
                return "invalid campus type";
            }

            $objectives = array_filter([
                $input['objectives1'] ?? null,
                $input['objectives2'] ?? null,
                $input['objectives3'] ?? null
            ]);

            if (!empty($objectives)) {
                $objStmt = $this->connection->prepare("
                    INSERT INTO activity_objectives (permit_id, permit_type, objective_text) 
                    VALUES (?, ?, ?)
                ");
                foreach ($objectives as $obj_text) {
                    if (trim($obj_text) !== '') {
                        $objStmt->bind_param("iss", $insert_id, $campus_type, $obj_text);
                        $objStmt->execute();
                    }
                }
                $objStmt->close();
            }

            $pending_permit_no = 'PPID-' . str_pad($this->getNextPermitSequence(), 4, '0', STR_PAD_LEFT);

            $updateStmt = $this->connection->prepare("UPDATE $table SET pending_permit_no = ? WHERE id = ?");
            $updateStmt->bind_param("si", $pending_permit_no, $insert_id);
            $updateStmt->execute();
            $updateStmt->close();

            $this->connection->commit();
            return true;

        } catch (Exception $e) {
            $this->connection->rollback();
            return false;
        }
    }

    public function getPermitsByOrg($acc_id) {
        $stmt = $this->connection->prepare("
            SELECT p.id, p.pending_permit_no, p.activity_title, p.nature_of_activity, 
                   p.start_date, p.end_date, p.time_start, p.time_end, p.time_created, 
                   p.venue, p.adviser, p.requirements_status,
                   'on' AS campus_type,
                   CASE 
                       WHEN p.permit_status = 'cancelled' THEN 'cancelled'
                       WHEN ap.id IS NOT NULL THEN 'approved' 
                       ELSE 'pending' 
                   END AS status
            FROM activity_permit_oncampus p
            LEFT JOIN approved_permits ap ON ap.permit_id = p.id AND ap.permit_type = 'on'
            WHERE p.acc_id = ?
            
            UNION ALL
            
            SELECT p.id, p.pending_permit_no, p.activity_title, p.nature_of_activity, 
                   p.start_date, p.end_date, p.time_start, p.time_end, p.time_created, 
                   p.venue, p.adviser, p.requirements_status,
                   'off' AS campus_type,
                   CASE 
                       WHEN p.permit_status = 'cancelled' THEN 'cancelled'
                       WHEN ap.id IS NOT NULL THEN 'approved' 
                       ELSE 'pending' 
                   END AS status
            FROM activity_permit_offcampus p
            LEFT JOIN approved_permits ap ON ap.permit_id = p.id AND ap.permit_type = 'off'
            WHERE p.acc_id = ?
            ORDER BY time_created DESC
        ");

        $stmt->bind_param("ii", $acc_id, $acc_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $permits = [];
        while ($row = $result->fetch_assoc()) {
            $permits[] = $row;
        }

        $stmt->close();
        return $permits;
    }

    public function getAllPermitsByOrg() {
        $stmt = $this->connection->prepare("
            SELECT p.id, p.pending_permit_no, p.activity_title, p.nature_of_activity, p.start_date, p.end_date, p.time_start, p.time_end, p.time_created, p.venue, p.adviser, p.acc_id,o.acc_id,o.org_name,
                   'on' AS campus_type,
                   'pending' AS status
            FROM activity_permit_oncampus p
            LEFT JOIN org_table o ON p.acc_id=o.acc_id
            LEFT JOIN approved_permits ap ON ap.permit_id = p.id AND ap.permit_type = 'on'
            WHERE ap.id IS NULL
            
            UNION ALL
            
            SELECT p.id, p.pending_permit_no, p.activity_title, p.nature_of_activity, p.start_date, p.end_date, p.time_start, p.time_end, p.time_created, p.venue, p.adviser, p.acc_id,o.acc_id,o.org_name,
                   'off' AS campus_type,
                   'pending' AS status
            FROM activity_permit_offcampus p
            LEFT JOIN org_table o ON p.acc_id=o.acc_id
            LEFT JOIN approved_permits ap ON ap.permit_id = p.id AND ap.permit_type = 'off'
            WHERE ap.id IS NULL
            
            ORDER BY id DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    public function getPermitById($id, $type) {
        $table = $type === 'on' ? 'activity_permit_oncampus' : 'activity_permit_offcampus';
        $stmt = $this->connection->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        if ($row) {
            $row['campus_type'] = $type;
        }
        return $row;
    }

    public function updateRequirementsStatus($permit_id, $campus_type, $status = 'submitted') {
        $type = (strpos(strtolower($campus_type), 'on') !== false) ? 'on' : 'off';
        $table = ($type === 'on') ? 'activity_permit_oncampus' : 'activity_permit_offcampus';

        $stmt = $this->connection->prepare("UPDATE {$table} SET requirements_status = ? WHERE id = ?");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("si", $status, $permit_id);
        $executed = $stmt->execute();
        $stmt->close();

        return $executed;
    }

    public function updateRejectRequirementsStatus($permit_id, $campus_type, $status = 'resubmit') {
        $type = (strpos(strtolower($campus_type), 'on') !== false) ? 'on' : 'off';
        $table = ($type === 'on') ? 'activity_permit_oncampus' : 'activity_permit_offcampus';

        $stmt = $this->connection->prepare("UPDATE {$table} SET requirements_status = ? WHERE id = ?");
        if (!$stmt) {
            error_log("Failed to prepare statement in updateRequirementsStatus: " . $this->connection->error);
            return false;
        }

        $bindType = is_numeric($permit_id) ? "si" : "ss";
        $stmt->bind_param($bindType, $status, $permit_id);

        $executed = $stmt->execute();
        $success = $executed && ($stmt->affected_rows >= 0);

        $stmt->close();
        return $success;
    }

    public function deletePermitRequirementFiles($permit_id, $campus_type = 'on') {
        $clean_type = strtolower(trim((string)$campus_type));
        $table = ($clean_type === 'off') ? 'permit_requirement_files_offcampus' : 'permit_requirement_files_oncampus';

        $stmt = $this->connection->prepare("SELECT stored_filename FROM {$table} WHERE permit_id = ?");
        if (!$stmt) {
            error_log("deletePermitRequirementFiles prepare failed: " . $this->connection->error);
            return;
        }

        $stmt->bind_param("i", $permit_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $uploadDir = "uploads/permit_requirements/";
        while ($row = $result->fetch_assoc()) {
            $filePath = $uploadDir . $row['stored_filename'];
            if (!empty($row['stored_filename']) && file_exists($filePath)) {
                unlink($filePath);
            }
        }
        $stmt->close();

        $deleteStmt = $this->connection->prepare("DELETE FROM {$table} WHERE permit_id = ?");
        $deleteStmt->bind_param("i", $permit_id);
        $deleteStmt->execute();
        $deleteStmt->close();
    }

    /**
     * Deletes a rejected permit application entirely (used by
     * PermitController::rejectRequirements()). This was missing from the
     * original PermitsModel — rejectRequirements() called
     * $this->model->deletePermit(...) which doesn't exist anywhere,
     * meaning that action would fatal error the moment it ran.
     */
    public function deletePermit($permit_id, $campus_type) {
        $type = (strpos(strtolower($campus_type), 'on') !== false) ? 'on' : 'off';
        $table = ($type === 'on') ? 'activity_permit_oncampus' : 'activity_permit_offcampus';

        $stmt = $this->connection->prepare("DELETE FROM {$table} WHERE id = ?");
        if (!$stmt) {
            error_log("deletePermit prepare failed: " . $this->connection->error);
            return false;
        }

        $stmt->bind_param("i", $permit_id);
        $executed = $stmt->execute();
        $stmt->close();

        return $executed;
    }

    public function sendRejectionEmail($permit_id, $campus_type, $rejection_reason) {
        require_once "helpers/mail_helper.php";

        $clean_type = strtolower(trim((string)$campus_type));
        $table = ($clean_type === 'off') ? 'activity_permit_offcampus' : 'activity_permit_oncampus';

        $stmt = $this->connection->prepare("
            SELECT t.email, o.org_name, p.activity_title
            FROM {$table} p
            JOIN org_table o ON o.acc_id = p.acc_id
            JOIN track_acc t ON t.id = o.acc_id
            WHERE p.id = ?
        ");

        if (!$stmt) {
            error_log("sendRejectionEmail prepare failed: " . $this->connection->error);
            return;
        }

        $stmt->bind_param("i", $permit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row || empty($row['email'])) {
            error_log("rejectPermit: no email found for permit_id $permit_id ($table), skipping notification");
            return;
        }

        $permitTitle = !empty($row['activity_title']) ? $row['activity_title'] : 'Permit Application';
        $subject = "Update regarding your permit application: " . $permitTitle;

        $body = "
            <p>Hi " . htmlspecialchars($row['org_name']) . ",</p>
            <p>We regret to inform you that your permit requirements for <strong>" . htmlspecialchars($permitTitle) . "</strong> have been rejected.</p>
            <p><strong>Reason for Rejection:</strong></p>
            <blockquote style='border-left: 3px solid #dc3545; padding-left: 10px; color: #555;'>
                " . nl2br(htmlspecialchars($rejection_reason)) . "
            </blockquote>
            <p>Please log in to your account to review the feedback and resubmit the necessary requirements.</p>
            <p>Best regards,<br>SOAU Team</p>
        ";

        $mailResult = sendEmail($row['email'], $subject, $body, 'Org Track');
        if ($mailResult !== true) {
            error_log("rejectPermit: failed to email recipient for permit_id $permit_id — $mailResult");
        }
    }
}