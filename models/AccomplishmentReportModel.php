<?php
require_once "BaseModel.php";

/**
 * Everything about accomplishment reports: creating one, listing them
 * (by org, all submitted, all approved), their attached files, and
 * staff rating/approval.
 */
class AccomplishmentReportModel extends BaseModel {

    public function getSubmittedReportsByOrg($acc_id) {
        $sql = "SELECT 
                    COALESCE(ap.generated_permit_no, '-') AS permit_id,
                    COALESCE(pon.activity_title, poff.activity_title, '-') AS activity_title,
                    ar.actual_submission AS submission_date,
                    ar.rating,
                    COALESCE(pon.permit_status, poff.permit_status, 'pending') AS permit_status,
                    CASE 
                        WHEN COALESCE(pon.permit_status, poff.permit_status) = 'cancelled' THEN 'Cancelled'
                        WHEN ar.rating IS NULL OR ar.rating = '' THEN 'Pending'
                        ELSE 'Complete'
                    END AS status,
                    ar.id AS report_id
                FROM accomplishment_report ar
                INNER JOIN approved_permits ap ON ar.approved_permit_id = ap.id
                LEFT JOIN activity_permit_oncampus pon 
                    ON ap.permit_id = pon.id AND ap.permit_type = 'on'
                LEFT JOIN activity_permit_offcampus poff 
                    ON ap.permit_id = poff.id AND ap.permit_type = 'off'
                WHERE COALESCE(pon.acc_id, poff.acc_id) = ?
                ORDER BY ar.actual_submission DESC";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $acc_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAllSubmittedReports() {
        $sql = "SELECT 
                    COALESCE(ap.generated_permit_no, '-') AS permit_id,
                    COALESCE(o.org_name, 'Unknown') AS organization,
                    COALESCE(pon.activity_title, poff.activity_title, '-') AS activity_title,
                    ar.actual_submission AS submission_date,
                    ar.rating,
                    ar.pending_rating,
                    COALESCE(pon.permit_status, poff.permit_status, 'pending') AS permit_status,
                    pc.reason AS cancellation_reason,
                    CASE 
                        WHEN COALESCE(pon.permit_status, poff.permit_status) = 'cancelled' THEN 'Cancelled'
                        WHEN ar.rating IS NULL OR ar.rating = '' THEN 'Pending'
                        ELSE 'Complete'
                    END AS status,
                    ar.id AS report_id
                FROM accomplishment_report ar
                INNER JOIN approved_permits ap ON ar.approved_permit_id = ap.id
                LEFT JOIN activity_permit_oncampus pon 
                    ON ap.permit_id = pon.id AND ap.permit_type = 'on'
                LEFT JOIN activity_permit_offcampus poff 
                    ON ap.permit_id = poff.id AND ap.permit_type = 'off'
                LEFT JOIN org_table o 
                    ON o.acc_id = COALESCE(pon.acc_id, poff.acc_id)
                LEFT JOIN permit_cancellations pc
                    ON pc.approved_permit_id = ap.id
                WHERE ar.rating IS NULL OR ar.rating = ''
                ORDER BY ar.actual_submission DESC";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($reports as &$report) {
            $report['files'] = $this->getAccomplishmentFiles($report['report_id']);
        }

        return $reports;
    }

    public function getAllApprovedReports() {
        $sql = "SELECT 
                    COALESCE(pon.pending_permit_no, poff.pending_permit_no, '-') AS permit_id,
                    COALESCE(o.org_name, 'Unknown') AS organization,
                    COALESCE(pon.activity_title, poff.activity_title, '-') AS activity_title,
                    ar.actual_submission AS submission_date,
                    ar.rating,
                    ar.pending_rating,
                    CASE 
                        WHEN ar.rating IS NULL OR ar.rating = '' THEN 'Pending'
                        ELSE 'Complete'
                    END AS status,
                    ar.id AS report_id
                FROM accomplishment_report ar
                INNER JOIN approved_permits ap ON ar.approved_permit_id = ap.id
                LEFT JOIN activity_permit_oncampus pon 
                    ON ap.permit_id = pon.id AND ap.permit_type = 'on'
                LEFT JOIN activity_permit_offcampus poff 
                    ON ap.permit_id = poff.id AND ap.permit_type = 'off'
                LEFT JOIN org_table o 
                    ON o.acc_id = COALESCE(pon.acc_id, poff.acc_id)
                WHERE ar.rating IS NOT NULL
                ORDER BY ar.actual_submission DESC";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($reports as &$report) {
            $report['files'] = $this->getAccomplishmentFiles($report['report_id']);
        }

        return $reports;
    }

    public function getAccomplishmentFiles($report_id) {
        $sql = "SELECT file_type, file_path, original_filename 
                FROM accomplishment_report_files 
                WHERE accomplishment_report_id = ?";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function saveAccomplishmentFile($accomplishment_report_id, $file_type, $file_path, $original_filename = '') {
        $sql = "INSERT INTO accomplishment_report_files (accomplishment_report_id, file_type, file_path, original_filename) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("isss", $accomplishment_report_id, $file_type, $file_path, $original_filename);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function createAccomplishmentReport($permit_identifier, $rating, $actual_submission) {
        $sql_lookup = "
            SELECT 
                permit_type, id
            FROM approved_permits 
            WHERE generated_permit_no = ?
            LIMIT 1
        ";

        $stmt_lookup = $this->connection->prepare($sql_lookup);
        $stmt_lookup->bind_param("s", $permit_identifier);
        $stmt_lookup->execute();

        $result = $stmt_lookup->get_result()->fetch_assoc();
        $stmt_lookup->close();

        if (!$result) {
            return false;
        }
        $permit_type = $result['permit_type'];
        $id          = $result['id'];

        // A permit can only have one accomplishment_report row (approved_permit_unique).
        // If one already exists — a retry, a double-click, or the permit was already
        // reported/cancelled earlier — reuse that row instead of attempting a
        // duplicate insert, which would otherwise throw on the unique key.
        $existingStmt = $this->connection->prepare(
            "SELECT id FROM accomplishment_report WHERE approved_permit_id = ? LIMIT 1"
        );
        $existingStmt->bind_param("i", $id);
        $existingStmt->execute();
        $existing = $existingStmt->get_result()->fetch_assoc();
        $existingStmt->close();

        if ($existing) {
            return (int) $existing['id'];
        }

        $sql = "INSERT INTO accomplishment_report (approved_permit_id, permit_type, pending_rating, actual_submission) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("isis", $id, $permit_type, $rating, $actual_submission);

        try {
            if ($stmt->execute()) {
                $insert_id = $this->connection->insert_id;
                $stmt->close();
                return $insert_id;
            }
        } catch (\mysqli_sql_exception $e) {
            // Race: another request inserted the row between our check and this
            // insert. Fetch and reuse it instead of failing the whole submission.
            $stmt->close();
            $existingStmt = $this->connection->prepare(
                "SELECT id FROM accomplishment_report WHERE approved_permit_id = ? LIMIT 1"
            );
            $existingStmt->bind_param("i", $id);
            $existingStmt->execute();
            $existing = $existingStmt->get_result()->fetch_assoc();
            $existingStmt->close();
            return $existing ? (int) $existing['id'] : false;
        }

        $stmt->close();
        return false;
    }

    /**
     * Sets permit_status = 'cancelled' on the underlying activity_permit_oncampus
     * / activity_permit_offcampus row, and records the reason in
     * permit_cancellations (a reason is only ever meaningful for a cancelled
     * permit, so it lives in its own table rather than a nullable column on
     * every permit row). Used when an org reports an activity as cancelled
     * instead of submitting a normal accomplishment report.
     */
    public function cancelPermitByGeneratedNo($permit_identifier, $reason = null) {
        $sql_lookup = "
            SELECT id, permit_type, permit_id
            FROM approved_permits
            WHERE generated_permit_no = ?
            LIMIT 1
        ";
        $stmt_lookup = $this->connection->prepare($sql_lookup);
        $stmt_lookup->bind_param("s", $permit_identifier);
        $stmt_lookup->execute();
        $result = $stmt_lookup->get_result()->fetch_assoc();
        $stmt_lookup->close();

        if (!$result) {
            return false;
        }

        $table = $result['permit_type'] === 'on'
            ? 'activity_permit_oncampus'
            : 'activity_permit_offcampus';
        $source_id         = $result['permit_id'];
        $approved_permit_id = $result['id'];

        $statusStmt = $this->connection->prepare(
            "UPDATE $table SET permit_status = 'cancelled' WHERE id = ?"
        );
        $statusStmt->bind_param("i", $source_id);
        $statusUpdated = $statusStmt->execute();
        $statusStmt->close();

        // reason is NOT NULL on permit_cancellations; never let a null/missing
        // caller value reach the query, even if something upstream forgets to pass one.
        $safeReason = trim((string) $reason);
        if ($safeReason === '') {
            $safeReason = 'No reason provided.';
        }

        $reasonStmt = $this->connection->prepare("
            INSERT INTO permit_cancellations (approved_permit_id, reason)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE reason = VALUES(reason), cancelled_at = CURRENT_TIMESTAMP
        ");
        $reasonStmt->bind_param("is", $approved_permit_id, $safeReason);
        $reasonStmt->execute();
        $reasonStmt->close();

        return $statusUpdated;
    }

    public function updateReportRatingAndApprove($report_id, $permit_id, $rating, $remarks) {
        $sql_permit = "
            SELECT 
                id, 
                permit_type,
                permit_id,
                approval_date,
                report_due,
                generated_permit_no
            FROM approved_permits WHERE generated_permit_no=?
            LIMIT 1
        ";

        $stmt_permit = $this->connection->prepare($sql_permit);
        $stmt_permit->bind_param("s", $permit_id);
        $stmt_permit->execute();
        $permit_result = $stmt_permit->get_result()->fetch_assoc();
        $stmt_permit->close();

        if (!$permit_result) {
            return false;
        }

        $approval_date      = $permit_result['approval_date'];
        $approved_permit_id = $permit_result['id'];
        $permit_type        = $permit_result['permit_type'];
        $p_id               = $permit_result['permit_id'];
        $report_due         = $permit_result['report_due'];

        $type = (strpos(strtolower($permit_type), 'on') !== false) ? 'on' : 'off';
        $table = ($type === 'on') ? 'activity_permit_oncampus' : 'activity_permit_offcampus';

        $sql_start_date = "SELECT start_date FROM " . $table . " WHERE id=? LIMIT 1";

        $stmt_start = $this->connection->prepare($sql_start_date);
        $stmt_start->bind_param("i", $p_id);
        $stmt_start->execute();
        $start_result = $stmt_start->get_result()->fetch_assoc();
        $stmt_start->close();

        $start_date = $start_result['start_date'] ?? null;

        $ap_points = 0;
        if (!empty($approval_date) && !empty($start_date)) {
            $appDate = new DateTime($approval_date);
            $stDate  = new DateTime($start_date);

            $diff_ap = $stDate->diff($appDate);
            $ap_points = $diff_ap->invert ? $diff_ap->days : -$diff_ap->days;
        }

        $actual_submission = date('Y-m-d');
        $ar_points = 0;

        if (!empty($report_due) && !empty($actual_submission)) {
            $dueDate = new DateTime($report_due);
            $subDate = new DateTime($actual_submission);

            $diff_ar = $dueDate->diff($subDate);
            $ar_points = ($diff_ar->invert ? $diff_ar->days : -$diff_ar->days) -3;
        }

        $ap_points_str = (string)$ap_points;
        $ar_points_str = (string)$ar_points;

        $sql = "INSERT INTO accomplishment_report 
                    (approved_permit_id, permit_type, rating, ap_points, ar_points,remarks, actual_submission) 
                VALUES (?, ?, ?, ?, ?,?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                    rating = VALUES(rating), 
                    ap_points = VALUES(ap_points),
                    ar_points = VALUES(ar_points), 
                    remarks = VALUES(remarks),
                    actual_submission = NOW()";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("isssss", $approved_permit_id, $permit_type, $rating, $ap_points_str, $ar_points_str, $remarks);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
}