<?php
require_once "BaseModel.php";

/**
 * Everything specific to staff accounts. Pulled out of UsersModel so
 * changes to staff logic (e.g. approval flow) don't require scrolling
 * through org-related code to find the right method.
 */
class StaffModel extends BaseModel {

    public function insertStaff($input) {
        if ($input["password"] !== $input["confirm_password"]) {
            return "Password Mismatch";
        }

        // Mirror the client-side checklist: 8+ chars, upper, lower, number, special char.
        $password = $input["password"];
        $isStrong = strlen($password) >= 8
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password)
            && preg_match('/[^A-Za-z0-9]/', $password);
        if (!$isStrong) {
            return "Password Weak";
        }

        if (!preg_match('/^\d{6}$/', $input["staff_id"])) {
            return "Invalid Staff ID";
        }

        if (!preg_match('/^09\d{2}-\d{3}-\d{4}$/', $input["contact_no"] ?? '')) {
            return "Invalid Contact Number";
        }

        $namePattern = '/^[A-Za-z\s\'\-]+$/';
        $namePatternOptional = '/^[A-Za-z\s\'\-]*$/'; // middle name may be blank
        if (!preg_match($namePattern, $input["fname"])
            || !preg_match($namePattern, $input["lname"])
            || !preg_match($namePatternOptional, $input["mname"] ?? '')
        ) {
            return "Invalid Name";
        }

        if (!str_ends_with($input["email"], "@bsu.edu.ph")) {
            return "Invalid Email Domain";
        }

        $check_email = $this->connection->prepare("SELECT id FROM track_acc WHERE email = ?");
        $check_email->bind_param("s", $input['email']);
        $check_email->execute();
        $check_email->store_result();
        if ($check_email->num_rows > 0) {
            $check_email->close();
            return "account taken";
        }
        $check_email->close();

        $check_staff = $this->connection->prepare("SELECT acc_id FROM staff_table WHERE staff_id = ?");
        $check_staff->bind_param("s", $input['staff_id']);
        $check_staff->execute();
        $check_staff->store_result();
        if ($check_staff->num_rows > 0) {
            $check_staff->close();
            return "Staff ID Taken";
        }
        $check_staff->close();

        $this->connection->begin_transaction();
        try {
            $role = "staff";
            $status = "Disabled";
            $hashed_password = password_hash($input["password"], PASSWORD_DEFAULT);
            $middle_name = $input["mname"] ?? '';

            $stmt1 = $this->connection->prepare("INSERT INTO track_acc (email, password, role, status) VALUES (?, ?, ?, ?)");
            $stmt1->bind_param("ssss", $input["email"], $hashed_password, $role, $status);
            $stmt1->execute();
            $stmt1->close();

            $acc_id = $this->connection->insert_id;

            $stmt2 = $this->connection->prepare("INSERT INTO staff_table (acc_id, first_name, middle_name, last_name, email, staff_id, contact_no) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("issssss", $acc_id, $input["fname"], $middle_name, $input["lname"], $input["email"], $input["staff_id"], $input["contact_no"]);
            $stmt2->execute();
            $stmt2->close();

            $this->connection->commit();
            return true;

        } catch (Exception $e) {
            $this->connection->rollback();
            return false;
        }
    }

    public function getStaffByEmail($email) {
        // Match on track_acc.email (the login-authoritative one, matching
        // $_SESSION['email']) rather than staff_table.email, so this still
        // resolves correctly even if the two ever drift out of sync.
        $stmt = $this->connection->prepare("
            SELECT s.acc_id, s.staff_id, s.first_name, s.middle_name, s.last_name,
                   s.contact_no, a.email, a.role
            FROM track_acc a
            INNER JOIN staff_table s ON s.acc_id = a.id
            WHERE a.email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function isEmailTakenByOther($email, $acc_id) {
        $stmt = $this->connection->prepare("SELECT id FROM track_acc WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $acc_id);
        $stmt->execute();
        $stmt->store_result();
        $taken = $stmt->num_rows > 0;
        $stmt->close();
        return $taken;
    }

    public function getStaffByAccId($acc_id) {
        $stmt = $this->connection->prepare("SELECT acc_id, staff_id, first_name, last_name, email FROM staff_table WHERE acc_id = ?");
        $stmt->bind_param("i", $acc_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function getAllStaffAccounts() {
        $query = "SELECT
                    s.acc_id,
                    s.staff_id,
                    s.first_name,
                    s.last_name,
                    s.contact_no,
                    a.email,
                    a.role,
                    a.status
                  FROM staff_table s
                  INNER JOIN track_acc a ON s.acc_id = a.id
                  WHERE a.role != 'admin'
                  ORDER BY s.acc_id DESC";

        $stmt = $this->connection->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        $staff_list = [];
        while ($row = $result->fetch_assoc()) {
            $staff_list[] = $row;
        }

        $stmt->close();
        return $staff_list;
    }

    public function updateStaffStatus($acc_id, $status) {
        $allowed_statuses = ['able', 'disabled', 'Disabled'];
        if (!in_array($status, $allowed_statuses, true)) {
            return false;
        }

        try {
            $stmt = $this->connection->prepare("UPDATE track_acc SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $acc_id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (\mysqli_sql_exception $e) {
            error_log("updateStaffStatus failed for acc_id $acc_id: " . $e->getMessage());
            return false;
        }
    }

    public function updateStaffProfile($acc_id, $first_name, $middle_name, $last_name, $email, $contact_no) {
        $this->connection->begin_transaction();
        try {
            $stmt1 = $this->connection->prepare("
                UPDATE staff_table
                SET first_name = ?, middle_name = ?, last_name = ?, email = ?, contact_no = ?
                WHERE acc_id = ?
            ");
            $stmt1->bind_param("sssssi", $first_name, $middle_name, $last_name, $email, $contact_no, $acc_id);
            $stmt1->execute();
            $stmt1->close();

            // Keep track_acc.email (used for login) in sync with staff_table.email —
            // otherwise a staff member changing their email here would lock
            // themselves out, and the admin's staff list would keep showing
            // the old address.
            $stmt2 = $this->connection->prepare("UPDATE track_acc SET email = ? WHERE id = ?");
            $stmt2->bind_param("si", $email, $acc_id);
            $stmt2->execute();
            $stmt2->close();

            $this->connection->commit();
            return true;
        } catch (\mysqli_sql_exception $e) {
            $this->connection->rollback();
            error_log("updateStaffProfile failed for acc_id $acc_id: " . $e->getMessage());
            return false;
        }
    }

    public function sendStaffStatusEmail($to_email, $staff_name, $status) {
        require_once __DIR__ . '/../helpers/mail_helper.php';
        $subject = "SOAU Account Status Update - BSU ORG-Track";

        $status_label = ($status === 'able') ? 'ACTIVATED' : 'DEACTIVATED';
        $status_color = ($status === 'able') ? '#2e7d32' : '#c62828';

        $action_text = ($status === 'able')
            ? "Your SOAU Staff account for BSU ORG-Track has been <strong>ACTIVATED</strong>. You may now sign in to your dashboard to process activity permits and organization applications."
            : "Your SOAU Staff account for BSU ORG-Track has been <strong>DEACTIVATED</strong>. If you believe this is an error, please contact the SOAU administrator.";

        $bodyHtml = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                <h2 style='color: #1b5e20; border-bottom: 2px solid #1b5e20; padding-bottom: 8px;'>BSU ORG-Track Notification</h2>
                <p>Hello <strong>" . htmlspecialchars($staff_name) . "</strong>,</p>
                <p>" . $action_text . "</p>
                <div style='margin: 20px 0; padding: 12px; background-color: #f5f5f5; border-left: 4px solid {$status_color}; font-weight: bold;'>
                    Account Status: <span style='color: {$status_color};'>{$status_label}</span>
                </div>
                <p style='font-size: 0.9em; color: #666;'>Regards,<br><strong>BSU Student Organizations and Affairs Unit (SOAU)</strong></p>
            </div>
        ";

        return sendEmail($to_email, $subject, $bodyHtml, 'BSU ORG-Track');
    }
}