<?php
require_once "BaseModel.php";

/**
 * Everything about permits that are ALREADY approved: the approve/undo
 * actions themselves, listing approved permits, activity-type stats,
 * and the staff "inline edit" feature on an approved permit's row.
 */
class ApprovedPermitModel extends BaseModel {

    public function approvePermit($permit_id, $permit_type, $report_due = null) {
        $sourceTable = $permit_type === 'on' ? 'activity_permit_oncampus' : 'activity_permit_offcampus';

        $stmt = $this->connection->prepare( "SELECT id FROM {$sourceTable} WHERE id = ?");
        $stmt->bind_param("i", $permit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $permit = $result->fetch_assoc(); 
        $stmt->close();

        if (!$permit) {
            return "permit not found";
        }

        $approval_date = date('Y-m-d');

        if ($report_due === null) {
            $workingDaysAdded = 0;
            $currentTimestamp = strtotime($approval_date);

            while ($workingDaysAdded < 7) {
                $currentTimestamp = strtotime('+1 day', $currentTimestamp);
                $dayOfWeek = date('N', $currentTimestamp);

                if ($dayOfWeek < 6) {
                    $workingDaysAdded++;
                }
            }
            $report_due = date('Y-m-d', $currentTimestamp);
        }

       $monthPrefix = date('m');

        $this->connection->begin_transaction();

        try {
            // Fetch the current highest permit sequence for this month
            $seqStmt = $this->connection->prepare("
                SELECT MAX(CAST(SUBSTRING_INDEX(generated_permit_no, '-', -1) AS UNSIGNED)) AS max_seq
                FROM approved_permits
                WHERE generated_permit_no LIKE CONCAT(?, '-%')
            ");
            $likePrefix = $monthPrefix;
            $seqStmt->bind_param("s", $likePrefix);
            $seqStmt->execute();
            $seqResult = $seqStmt->get_result();
            $seqRow = $seqResult->fetch_assoc();
            $seqStmt->close();

            // Increment max value by 1 (or start at 1 if none exist for this month)
            $nextSequence = ($seqRow['max_seq'] !== null) ? ((int)$seqRow['max_seq'] + 1) : 1;
            $generated_permit_no = $monthPrefix . '-' . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

            $insertStmt = $this->connection->prepare("
                INSERT INTO approved_permits
                    (permit_id, permit_type, approval_date, report_due, generated_permit_no)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertStmt->bind_param(
                "issss",
                $permit_id,
                $permit_type,
                $approval_date,
                $report_due,
                $generated_permit_no
            );
            $insertStmt->execute();

            if ($insertStmt->affected_rows === 0) {
                $insertStmt->close();
                $this->connection->rollback();
                return "insert failed";
            }
            $insertStmt->close();

            $statusStmt = $this->connection->prepare("
                UPDATE {$sourceTable}
                SET permit_status = 'approved'
                WHERE id = ?
            ");
            $statusStmt->bind_param("i", $permit_id);
            $statusStmt->execute();
            $statusStmt->close();

            $this->connection->commit();
            return true;

        } catch (Exception $e) {
            $this->connection->rollback();
            return "approval failed: " . $e->getMessage();
        }
    }

    public function undoApprovePermit($permit_id, $permit_type) {
        $sourceTable = ($permit_type === 'on') ? 'activity_permit_oncampus' : 'activity_permit_offcampus';

        $this->connection->begin_transaction();

        try {
            $selectStmt = $this->connection->prepare("
                SELECT permit_id, generated_permit_no 
                FROM approved_permits
                WHERE generated_permit_no = ? AND permit_type = ?
            ");
            $selectStmt->bind_param("ss", $permit_id, $permit_type);
            $selectStmt->execute();
            $result = $selectStmt->get_result();
            $permitData = $result->fetch_assoc();
            $selectStmt->close();

            if (!$permitData) {
                $this->connection->rollback();
                return "approved permit not found";
            }

            $sourceDbId = (int)$permitData['permit_id'];
            $generatedPermitNo = $permitData['generated_permit_no'];

            $deleteStmt = $this->connection->prepare("
                DELETE FROM approved_permits 
                WHERE generated_permit_no = ? AND permit_type = ?
            ");
            $deleteStmt->bind_param("ss", $permit_id, $permit_type);
            $deleteStmt->execute();

            if ($deleteStmt->affected_rows === 0) {
                $deleteStmt->close();
                $this->connection->rollback();
                return "failed to delete approved permit";
            }
            $deleteStmt->close();

            $updateStmt = $this->connection->prepare("
                UPDATE {$sourceTable} 
                SET requirements_status = 'submitted', permit_status = 'pending' 
                WHERE id = ?
            ");
            $updateStmt->bind_param("i", $sourceDbId);
            $updateStmt->execute();
            $updateStmt->close();

            $this->connection->commit();

            return [
                'success' => true,
                'source_id' => $sourceDbId,
                'generated_permit_no' => $generatedPermitNo
            ];

        } catch (Exception $e) {
            $this->connection->rollback();
            return "revert failed: " . $e->getMessage();
        }
    }

    public function getApprovedPermitsByOrg($acc_id) {
        $sql = "SELECT 
                    ap.generated_permit_no AS permit_id,
                    COALESCE(o.org_name, 'Unknown') AS organization,
                    COALESCE(pon.activity_title, poff.activity_title, '-') AS activity_title,
                    COALESCE(pon.nature_of_activity, poff.nature_of_activity, '-') AS type,
                    COALESCE(pon.start_date, poff.start_date, '-') AS start_date,
                    COALESCE(pon.end_date, poff.end_date, '-') AS end_date,
                    COALESCE(pon.venue, poff.venue, '-') AS venue,
                    DATE(ap.approval_date) AS approval_date,
                    COALESCE(ap.report_due, '-') AS report_due,
                    CASE 
                        WHEN ar.id IS NOT NULL THEN 'submitted'
                        ELSE 'pending'
                    END AS report_status,
                    COALESCE(ar.actual_submission, 'Not Submitted') AS actual_submission,
                    COALESCE(ar.rating, '-') AS rating
                FROM approved_permits ap
                LEFT JOIN activity_permit_oncampus pon 
                    ON ap.permit_id = pon.id AND ap.permit_type = 'on'
                LEFT JOIN activity_permit_offcampus poff 
                    ON ap.permit_id = poff.id AND ap.permit_type = 'off'
                LEFT JOIN org_table o 
                    ON o.acc_id = COALESCE(pon.acc_id, poff.acc_id)
                LEFT JOIN accomplishment_report ar 
                    ON ap.id = ar.approved_permit_id
                WHERE COALESCE(pon.acc_id, poff.acc_id) = ?
                ORDER BY ap.approval_date DESC";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $acc_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAllApprovedPermits() {
        $sql = "SELECT 
                    COALESCE(ap.generated_permit_no, '-') AS permit_id,
                    COALESCE(o.org_name, 'Unknown') AS organization,
                    ap.permit_type AS campus_type,
                    COALESCE(pon.activity_title, poff.activity_title, '-') AS activity_title,
                    COALESCE(pon.nature_of_activity, poff.nature_of_activity, '-') AS type,
                    COALESCE(pon.start_date, poff.start_date, '-') AS start_date,
                    COALESCE(pon.end_date, poff.end_date, '-') AS end_date,
                    COALESCE(pon.time_start, CAST(poff.time_start AS CHAR), '-') AS start_time,
                    COALESCE(pon.time_end, CAST(poff.time_end AS CHAR), '-') AS end_time,
                    COALESCE(pon.venue, poff.venue, '-') AS venue,
                    DATE(ap.approval_date) AS approval_date,
                    COALESCE(ap.report_due, '-') AS report_due,
                    COALESCE(ar.actual_submission, 'Pending') AS actual_submission,
                    COALESCE(ar.rating, '-') AS rating,
                    COALESCE(ar.ap_points, '0') AS ap_points,
                    COALESCE(ar.ar_points, '0') AS ar_points,
                    COALESCE(ar.remarks, '') AS remarks
                FROM approved_permits ap
                LEFT JOIN activity_permit_oncampus pon 
                    ON ap.permit_id = pon.id AND ap.permit_type = 'on'
                LEFT JOIN activity_permit_offcampus poff 
                    ON ap.permit_id = poff.id AND ap.permit_type = 'off'
                LEFT JOIN org_table o 
                    ON o.acc_id = COALESCE(pon.acc_id, poff.acc_id)
                LEFT JOIN accomplishment_report ar 
                    ON ap.id = ar.approved_permit_id
                ORDER BY ap.approval_date DESC";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getNatureOfActivityBreakdown() {
        $sql = "
            SELECT 
                cp.nature_of_activity,
                COUNT(*) AS total_count,
                SUM(CASE WHEN cp.permit_type = 'on' THEN 1 ELSE 0 END) AS oncampus_count,
                SUM(CASE WHEN cp.permit_type = 'off' THEN 1 ELSE 0 END) AS offcampus_count
            FROM (
                SELECT id, nature_of_activity, requirements_status, 'on' AS permit_type 
                FROM activity_permit_oncampus
                UNION ALL
                SELECT id, nature_of_activity, requirements_status, 'off' AS permit_type 
                FROM activity_permit_offcampus
            ) AS cp
            INNER JOIN approved_permits ap 
                ON ap.permit_id = cp.id AND ap.permit_type = cp.permit_type
            WHERE cp.nature_of_activity IS NOT NULL 
              AND cp.nature_of_activity != '' 
            GROUP BY cp.nature_of_activity
            ORDER BY total_count DESC
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $results;
    }

    /**
     * Staff's inline-edit feature on an approved permit row: updates the
     * approval dates, the underlying activity details, and the linked
     * accomplishment report (if any) in one transaction.
     */
    public function updateInlinePermitData($data) {
        $permitIdentifier = $data['permit_id'] ?? null;
        if (!$permitIdentifier) {
            return ['success' => false, 'error' => 'No permit identifier provided.'];
        }

        $cleanDate = function($val) {
            $trimmed = trim($val ?? '');
            return ($trimmed === '' || $trimmed === '-' || $trimmed === '0000-00-00') ? null : $trimmed;
        };

        $cleanInt = function($val) {
            $trimmed = trim($val ?? '');
            return ($trimmed === '' || $trimmed === '-') ? 0 : (int)$trimmed;
        };

        $sqlLookup = "SELECT 
                        ap.id AS approved_permit_id, 
                        ap.permit_id AS base_permit_id, 
                        ap.permit_type 
                      FROM approved_permits ap
                      LEFT JOIN activity_permit_oncampus pon ON ap.permit_id = pon.id AND ap.permit_type = 'on'
                      LEFT JOIN activity_permit_offcampus poff ON ap.permit_id = poff.id AND ap.permit_type = 'off'
                      WHERE pon.pending_permit_no = ? OR poff.pending_permit_no = ? OR ap.id = ? 
                      LIMIT 1";

        $stmt = $this->connection->prepare($sqlLookup);
        if (!$stmt) {
            return ['success' => false, 'error' => 'Lookup Prepare Error: ' . $this->connection->error];
        }

        $stmt->bind_param("sss", $permitIdentifier, $permitIdentifier, $permitIdentifier);
        $stmt->execute();
        $apRecord = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$apRecord) {
            return ['success' => false, 'error' => 'Permit identifier "' . $permitIdentifier . '" not found in database.'];
        }

        $approved_permit_id = (int)$apRecord['approved_permit_id'];
        $base_permit_id     = (int)$apRecord['base_permit_id'];
        $permit_type        = $apRecord['permit_type'];

        $approval_date      = $cleanDate($data['approval_date'] ?? null);
        $report_due         = $cleanDate($data['report_due'] ?? null);
        $activity_title     = trim($data['activity_title'] ?? '');
        $nature_of_activity = trim($data['type'] ?? '');
        $start_date         = $cleanDate($data['start_date'] ?? null);
        $end_date           = $cleanDate($data['end_date'] ?? null);
        $time_start         = !empty($data['start_time']) && $data['start_time'] !== '-' ? trim($data['start_time']) : null;
        $time_end           = !empty($data['end_time']) && $data['end_time'] !== '-' ? trim($data['end_time']) : null;
        $venue              = trim($data['venue'] ?? '');

        $actual_submission = $cleanDate($data['actual_submission'] ?? null);
        $rating            = (!empty($data['rating']) && $data['rating'] !== '-') ? trim($data['rating']) : null;
        $ap_points         = $cleanInt($data['ap_points'] ?? 0);
        $ar_points         = $cleanInt($data['ar_points'] ?? 0);
        $remarks           = trim($data['remarks'] ?? '');

        $this->connection->begin_transaction();

        try {
            $stmtAp = $this->connection->prepare(
                "UPDATE approved_permits SET approval_date = ?, report_due = ? WHERE id = ?"
            );
            if (!$stmtAp) throw new Exception("approved_permits error: " . $this->connection->error);
            $stmtAp->bind_param("ssi", $approval_date, $report_due, $approved_permit_id);
            $stmtAp->execute();
            $stmtAp->close();

            $permitTable = ($permit_type === 'off') ? 'activity_permit_offcampus' : 'activity_permit_oncampus';
            $sqlPermit   = "UPDATE {$permitTable} SET 
                                activity_title = ?, 
                                nature_of_activity = ?,
                                start_date = ?, 
                                end_date = ?, 
                                time_start = ?, 
                                time_end = ?, 
                                venue = ? 
                            WHERE id = ?";

            $stmtP = $this->connection->prepare($sqlPermit);
            if (!$stmtP) throw new Exception("{$permitTable} error: " . $this->connection->error);
            $stmtP->bind_param(
                "sssssssi",
                $activity_title,
                $nature_of_activity,
                $start_date,
                $end_date,
                $time_start,
                $time_end,
                $venue,
                $base_permit_id
            );
            $stmtP->execute();
            $stmtP->close();

            $stmtArCheck = $this->connection->prepare("SELECT id FROM accomplishment_report WHERE approved_permit_id = ?");
            $stmtArCheck->bind_param("i", $approved_permit_id);
            $stmtArCheck->execute();
            $arRecord = $stmtArCheck->get_result()->fetch_assoc();
            $stmtArCheck->close();

            if ($arRecord) {
                $stmtAr = $this->connection->prepare(
                    "UPDATE accomplishment_report SET actual_submission = ?, rating = ?, ap_points = ?, ar_points = ?, remarks = ? WHERE id = ?"
                );
                if (!$stmtAr) throw new Exception("accomplishment_report update error: " . $this->connection->error);
                $stmtAr->bind_param("ssiisi", $actual_submission, $rating, $ap_points, $ar_points, $remarks, $arRecord['id']);
                $stmtAr->execute();
                $stmtAr->close();
            } else {
                $stmtAr = $this->connection->prepare(
                    "INSERT INTO accomplishment_report (approved_permit_id, permit_type, actual_submission, rating, ap_points, ar_points, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                if (!$stmtAr) throw new Exception("accomplishment_report insert error: " . $this->connection->error);
                $stmtAr->bind_param("isssiis", $approved_permit_id, $permit_type, $actual_submission, $rating, $ap_points, $ar_points, $remarks);
                $stmtAr->execute();
                $stmtAr->close();
            }

            $this->connection->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->connection->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}