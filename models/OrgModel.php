<?php
require_once "BaseModel.php";

/**
 * Everything specific to organization (RSO) accounts: registration,
 * renewal, approval/rejection, and profile management. Pulled out of
 * UsersModel for the same reason as StaffModel — one topic per file.
 */
class OrgModel extends BaseModel {

    public function insertOrg($input, $files) {
        $check_email_stmt = $this->connection->prepare("SELECT COUNT(*) FROM track_acc WHERE email = ?");
        $check_email_stmt->bind_param("s", $input['email']);
        $check_email_stmt->execute();
        $email_res = $check_email_stmt->get_result();
        $email_row = $email_res->fetch_row();
        $email_count = $email_row[0];
        $check_email_stmt->close();

        if ($email_count > 0) {
            return "Error: An account with this email already exists.";
        }

        $uploadDir = __DIR__ . "/../uploads/org_documents/";

        if (!is_dir($uploadDir)) {
            if (!@mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                return "Error: Could not create upload directory at {$uploadDir}. Check folder permissions.";
            }
        }
        if (!is_writable($uploadDir)) {
            return "Error: Upload directory ({$uploadDir}) is not writable.";
        }
        if (!class_exists('finfo')) {
            return "Error: The PHP 'fileinfo' extension is not enabled.";
        }

        $allowedTypes = [
            'pdf'  => ['application/pdf'],
            'jpg'  => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png'  => ['image/png']
        ];

        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $fileKeys = ['LOA', 'AAF', 'POBIC', 'TAP', 'RCAB'];
        $fileNames = [];

        foreach ($fileKeys as $key) {
            if (isset($files[$key]) && $files[$key]['error'] === UPLOAD_ERR_OK) {
                $tmpName      = $files[$key]['tmp_name'];
                $fileSize     = $files[$key]['size'];
                $originalName = basename($files[$key]['name']);
                $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if ($fileSize > $maxFileSize) {
                    return "Error: File '{$originalName}' exceeds 5MB limit.";
                }
                if (!array_key_exists($ext, $allowedTypes)) {
                    return "Error: Invalid file type for '{$originalName}'. Only PDF, JPG, and PNG are allowed.";
                }

                $finfo        = new finfo(FILEINFO_MIME_TYPE);
                $realMimeType = $finfo->file($tmpName);

                if (!in_array($realMimeType, $allowedTypes[$ext])) {
                    return "Error: Content of '{$originalName}' ({$realMimeType}) does not match extension '.{$ext}'.";
                }

                $newFileName = time() . '_' . uniqid() . '.' . $ext;
                $targetPath  = $uploadDir . $newFileName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $fileNames[$key] = $newFileName;
                } else {
                    return "Error: Failed to move uploaded file '{$originalName}'. Check folder permissions.";
                }
            } else {
                $fileNames[$key] = null;
            }
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->connection->begin_transaction();

        try {
            $role                 = "organization";
            $status               = "Disabled";
            $placeholder_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

            $stmt1 = $this->connection->prepare("INSERT INTO track_acc (email, role, status, password) VALUES (?, ?, ?, ?)");
            $stmt1->bind_param("ssss", $input["email"], $role, $status, $placeholder_password);
            $stmt1->execute();
            $acc_id = $this->connection->insert_id;
            $stmt1->close();

            $orgDesc  = ($input['org_type'] === "existing") ? "" : ($input["org_description"] ?? "");
            $instDesc = ($input['org_type'] === "existing") ? "" : ($input["institution_description"] ?? "");

            $stmt2 = $this->connection->prepare("
                INSERT INTO org_table (
                    acc_id, email, type, org_name, first_name, last_name, middle_name, contact_no, org_type, org_description, Institution_description
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt2->bind_param(
                "issssssssss",
                $acc_id,
                $input["email"],
                $input["type"],
                $input["org_name"],
                $input["fname"],
                $input["lname"],
                $input["mname"],
                $input["contact"],
                $input["org_type"],
                $orgDesc,
                $instDesc
            );
            $stmt2->execute();
            $org_id = $this->connection->insert_id;
            $stmt2->close();

            $hasFiles = array_filter($fileNames);
            if (!empty($hasFiles)) {
                $stmtDoc = $this->connection->prepare("INSERT INTO org_documents (org_id, doc_type, file_path) VALUES (?, ?, ?)");
                foreach ($fileNames as $docType => $filePath) {
                    if (!empty($filePath)) {
                        $docTypeUpper = strtoupper($docType);
                        $stmtDoc->bind_param("iss", $org_id, $docTypeUpper, $filePath);
                        $stmtDoc->execute();
                    }
                }
                $stmtDoc->close();
            }

            $this->connection->commit();
            return true;

        } catch (Exception $e) {
            $this->connection->rollback();

            foreach ($fileNames as $uploadedFile) {
                if ($uploadedFile && file_exists($uploadDir . $uploadedFile)) {
                    unlink($uploadDir . $uploadedFile);
                }
            }

            return "Database Error: " . $e->getMessage();
        }
    }

    public function RenewalOrg($input, $files) {
        $uploadDir = "uploads/org_documents/";
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                return "Error: Could not create the upload directory ($uploadDir). Check folder permissions on the server.";
            }
        }
        if (!is_writable($uploadDir)) {
            return "Error: Upload directory ($uploadDir) is not writable. Check folder permissions on the server.";
        }
        if (!class_exists('finfo')) {
            return "Error: The PHP 'fileinfo' extension is not enabled on this server, so uploaded file types can't be verified.";
        }

        $allowedTypes = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png'
        ];
        $maxFileSize = 5 * 1024 * 1024;

        $fileKeys = ['LOA', 'AAF', 'POBIC', 'TAP', 'RCAB'];
        $fileNames = [];

        foreach ($fileKeys as $key) {
            if (isset($files[$key]) && $files[$key]['error'] === UPLOAD_ERR_OK) {
                $tmpName      = $files[$key]['tmp_name'];
                $fileSize     = $files[$key]['size'];
                $originalName = basename($files[$key]['name']);
                $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if ($fileSize > $maxFileSize) {
                    return "Error: File '{$originalName}' exceeds the maximum allowed size of 5MB.";
                }
                if (!array_key_exists($ext, $allowedTypes)) {
                    return "Error: Invalid file type for '{$originalName}'. Only PDF, JPG, and PNG files are allowed.";
                }

                $finfo        = new finfo(FILEINFO_MIME_TYPE);
                $realMimeType = $finfo->file($tmpName);

                if ($realMimeType !== $allowedTypes[$ext]) {
                    return "Error: Content of '{$originalName}' does not match its file extension.";
                }

                $newFileName = time() . '_' . uniqid() . '.' . $ext;
                $targetPath  = $uploadDir . $newFileName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $fileNames[$key] = $newFileName;
                } else {
                    $fileNames[$key] = null;
                }
            } else {
                $fileNames[$key] = null;
            }
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->connection->begin_transaction();

        try {
            $email = $input['email'];

            $stmtFind = $this->connection->prepare("SELECT id, acc_id FROM org_table WHERE email = ?");
            $stmtFind->bind_param("s", $email);
            $stmtFind->execute();
            $res = $stmtFind->get_result();
            $orgRow = $res->fetch_assoc();
            $stmtFind->close();

            if (!$orgRow) {
                throw new Exception("Organization record not found for email: {$email}");
            }

            $org_id = $orgRow['id'];
            $acc_id = $orgRow['acc_id'];

            $stmtOldFiles = $this->connection->prepare("SELECT file_path FROM org_documents WHERE org_id = ?");
            $stmtOldFiles->bind_param("i", $org_id);
            $stmtOldFiles->execute();
            $oldFilesRes = $stmtOldFiles->get_result();

            while ($row = $oldFilesRes->fetch_assoc()) {
                $oldFilePath = $uploadDir . $row['file_path'];
                if (!empty($row['file_path']) && file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }
            $stmtOldFiles->close();

            $stmtDelDocs = $this->connection->prepare("DELETE FROM org_documents WHERE org_id = ?");
            $stmtDelDocs->bind_param("i", $org_id);
            $stmtDelDocs->execute();
            $stmtDelDocs->close();

            $hasFiles = array_filter($fileNames);
            if (!empty($hasFiles)) {
                $stmtDoc = $this->connection->prepare("INSERT INTO org_documents (org_id, doc_type, file_path) VALUES (?, ?, ?)");
                foreach ($fileNames as $docType => $filePath) {
                    if (!empty($filePath)) {
                        $docTypeUpper = strtoupper($docType);
                        $stmtDoc->bind_param("iss", $org_id, $docTypeUpper, $filePath);
                        $stmtDoc->execute();
                    }
                }
                $stmtDoc->close();
            }

            $stmtUpdateOrg = $this->connection->prepare("
                UPDATE org_table
                SET type = ?,
                    org_name = ?,
                    first_name = ?,
                    last_name = ?,
                    contact_no = ?,
                    org_type = ?
                WHERE id = ?
            ");
            $stmtUpdateOrg->bind_param(
                "ssssssi",
                $input["type"],
                $input["org_name"],
                $input["fname"],
                $input["lname"],
                $input["contact"],
                $input["org_type"],
                $org_id
            );
            $stmtUpdateOrg->execute();
            $stmtUpdateOrg->close();

            $stmtUpdateAcc = $this->connection->prepare("UPDATE track_acc SET status = 'Disabled' WHERE id = ?");
            $stmtUpdateAcc->bind_param("i", $acc_id);
            $stmtUpdateAcc->execute();
            $stmtUpdateAcc->close();

            $this->connection->commit();
            return true;

        } catch (Exception $e) {
            $this->connection->rollback();

            foreach ($fileNames as $uploadedFile) {
                if ($uploadedFile && file_exists($uploadDir . $uploadedFile)) {
                    unlink($uploadDir . $uploadedFile);
                }
            }

            return "Database Error: " . $e->getMessage();
        }
    }

    public function getOrgByEmail($email) {
        $stmt = $this->connection->prepare("SELECT acc_id, org_name, first_name, last_name, contact_no, org_status FROM org_table WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function getOrgsByStatus($status) {
        $stmt = $this->connection->prepare("
            SELECT
                o.acc_id,
                o.org_name,
                o.org_status,
                o.first_name,
                o.last_name,
                o.middle_name,
                o.contact_no,
                o.org_type,
                t.email,
                t.status,
                MAX(CASE WHEN d.doc_type = 'LOA' THEN d.file_path END) AS LOA,
                MAX(CASE WHEN d.doc_type = 'AAF' THEN d.file_path END) AS AAF,
                MAX(CASE WHEN d.doc_type = 'POBIC' THEN d.file_path END) AS POBIC,
                MAX(CASE WHEN d.doc_type = 'TAP' THEN d.file_path END) AS TAP,
                MAX(CASE WHEN d.doc_type = 'RCAB' THEN d.file_path END) AS RCAB
            FROM org_table o
            JOIN track_acc t ON t.id = o.acc_id
            LEFT JOIN org_documents d ON d.org_id = o.id
            WHERE t.status = ?
            GROUP BY o.id, t.id
            ORDER BY o.acc_id DESC
        ");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    public function updateOrgProfile($acc_id, $org_name, $first_name, $last_name, $contact_no, $email) {
        $stmt = $this->connection->prepare("
            UPDATE org_table
            SET org_name = ?, first_name = ?, last_name = ?, contact_no = ?, email = ?
            WHERE acc_id = ?
        ");
        $stmt->bind_param("sssssi", $org_name, $first_name, $last_name, $contact_no, $email, $acc_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function approveOrg($acc_id, $status_type, $email, $org_name) {
        $this->connection->begin_transaction();
        try {
            $stmt1 = $this->connection->prepare("UPDATE org_table SET org_status = ?, email = ?, org_name = ? WHERE acc_id = ?");
            $stmt1->bind_param("sssi", $status_type, $email, $org_name, $acc_id);
            $stmt1->execute();
            $stmt1->close();

            $status = 'able';
            $generated_password = "12345"; // TODO: generate randomly (bin2hex(random_bytes(6))) — see note below
            $password = password_hash($generated_password, PASSWORD_DEFAULT);
            $stmt2 = $this->connection->prepare("UPDATE track_acc SET status = ?, email = ?, password = ? WHERE id = ?");
            $stmt2->bind_param("sssi", $status, $email, $password, $acc_id);
            $stmt2->execute();
            $stmt2->close();

            $this->connection->commit();
            $this->sendApprovalEmail($acc_id, $generated_password, $status_type);

            return true;
        } catch (Exception $e) {
            $this->connection->rollback();
            return false;
        }
    }

    public function rejectOrg($acc_id, $comment) {
        $this->sendOrgRejectionEmail($acc_id, $comment);

        $stmt = $this->connection->prepare("
            SELECT od.file_path
            FROM org_documents od
            JOIN org_table o ON o.id = od.org_id
            WHERE o.acc_id = ?
        ");
        $stmt->bind_param("i", $acc_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $uploadDir = "uploads/org_documents/";
        while ($row = $result->fetch_assoc()) {
            $filePath = $uploadDir . $row['file_path'];
            if (!empty($row['file_path']) && file_exists($filePath)) {
                unlink($filePath);
            }
        }
        $stmt->close();

        $deleteStmt = $this->connection->prepare("DELETE FROM track_acc WHERE id = ?");
        $deleteStmt->bind_param("i", $acc_id);
        $success = $deleteStmt->execute();
        $deleteStmt->close();

        return $success;
    }

    public function setAllOrgsToRenewal() {
        $stmt = $this->connection->prepare("
            UPDATE track_acc
            SET status = 'Renewal'
            WHERE role = 'organization' OR role = 'org'
        ");
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    private function sendApprovalEmail($acc_id, $generated_password, $status_type) {
        require_once __DIR__ . '/../helpers/mail_helper.php';

        $stmt = $this->connection->prepare("
            SELECT t.email, o.org_name
            FROM track_acc t
            JOIN org_table o ON o.acc_id = t.id
            WHERE t.id = ?
        ");
        $stmt->bind_param("i", $acc_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row || empty($row['email'])) {
            error_log("approveOrg: no email found for acc_id $acc_id, skipping notification");
            return;
        }

        $subject = "Your organization account has been approved";
        $body = "
            <p>Hi " . htmlspecialchars($row['org_name']) . ",</p>
            <p>Your organization has been approved with status: <strong>" . htmlspecialchars($status_type) . "</strong>.</p>
            <p>You can now log in with:</p>
            <p>Email: " . htmlspecialchars($row['email']) . "<br>
            Password: <strong>" . htmlspecialchars($generated_password) . "</strong></p>
            <p>Please log in and change your password as soon as possible.</p>
        ";

        if (in_array($status_type, ['Probationary', 'Conditional'], true)) {
            $body .= "
                <p style='background:#fff3cd; border-left:4px solid #ffc107; padding:10px 14px; margin:16px 0;'>
                    <strong>Next steps:</strong> Please come in for an interview at the SOAU office,
                    and bring printed hard copies of the documents you uploaded during registration.
                </p>
            ";
        } elseif ($status_type === 'Full Recognition') {
            $body .= "
                <p style='background:#d4edda; border-left:4px solid #28a745; padding:10px 14px; margin:16px 0;'>
                    <strong>Next step:</strong> Please bring printed hard copies of the documents
                    you uploaded during registration to the SOAU office.
                </p>
            ";
        }

        $mailResult = sendEmail($row['email'], $subject, $body, 'Org Track');
        if ($mailResult !== true) {
            error_log("approveOrg: failed to email acc_id $acc_id — $mailResult");
        }
    }

    private function sendOrgRejectionEmail($acc_id, $comment) {
        require_once __DIR__ . '/../helpers/mail_helper.php';

        $stmt = $this->connection->prepare("
            SELECT t.email, o.org_name
            FROM track_acc t
            JOIN org_table o ON o.acc_id = t.id
            WHERE t.id = ?
        ");
        $stmt->bind_param("i", $acc_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row || empty($row['email'])) {
            error_log("rejectOrg: no email found for acc_id $acc_id, skipping notification");
            return;
        }

        $subject = "Update regarding your organization application";
        $body = "
            <p>Hi " . htmlspecialchars($row['org_name']) . ",</p>
            <p>We regret to inform you that your organization registration application has been rejected.</p>
            <p><strong>Reason / Remarks:</strong></p>
            <blockquote style='border-left: 3px solid #dc3545; padding-left: 10px; color: #555;'>
                " . nl2br(htmlspecialchars($comment)) . "
            </blockquote>
            <p>If you have any questions or would like to re-apply, please contact the SOAU office.</p>
            <p>Best regards,<br>SOAU Team</p>
        ";

        $mailResult = sendEmail($row['email'], $subject, $body, 'Org Track');
        if ($mailResult !== true) {
            error_log("rejectOrg: failed to email acc_id $acc_id — $mailResult");
        }
    }
}