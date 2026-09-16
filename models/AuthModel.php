<?php
require_once "BaseModel.php";

/**
 * Handles authentication concerns shared by every account type:
 * logging in, changing a password, and resolving an email to an acc_id.
 * Split out of UsersModel so staff-specific and org-specific data logic
 * live in their own files (StaffModel / OrgModel) instead of one
 * 700+ line file.
 */
class AuthModel extends BaseModel {

    public function getAccIdByEmail($email) {
        $stmt = $this->connection->prepare("SELECT id FROM track_acc WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? $row['id'] : null;
    }

    public function login($input) {
        $stmt = $this->connection->prepare("SELECT * FROM track_acc WHERE email = ?");
        $stmt->bind_param("s", $input['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        $result = $result->fetch_assoc();

        if ($result) {
            if ($result['status'] == 'able') {
                if (password_verify($input["password"], $result["password"])) {
                    if ($result['role'] == 'staff') {
                        return 'staff';
                    } elseif ($result['role'] == 'organization') {
                        return 'organization';
                    } else {
                        return $result['role']; // admin, etc.
                    }
                } else {
                    return 'invalid credentials';
                }
            } elseif ($result['status'] == 'Renewal') {
                if (password_verify($input["password"], $result["password"])) {
                    if ($result['role'] == 'organization') {
                        return 'org_renewal';
                    } else {
                        return 'an error have occur';
                    }
                } else {
                    return 'invalid credentials';
                }
            } else {
                return 'Disabled';
            }
        }

        return "invalid credentials";
    }

    public function changePassword($acc_id, $current_password, $new_password) {
        if (empty($acc_id)) {
            return "not logged in";
        }
        if (empty($current_password) || empty($new_password)) {
            return "missing fields";
        }

        $isStrong = strlen($new_password) >= 8
            && preg_match('/[A-Z]/', $new_password)
            && preg_match('/[a-z]/', $new_password)
            && preg_match('/[0-9]/', $new_password)
            && preg_match('/[^A-Za-z0-9]/', $new_password);
        if (!$isStrong) {
            return "weak password";
        }

        $stmt = $this->connection->prepare("SELECT password FROM track_acc WHERE id = ?");
        $stmt->bind_param("i", $acc_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return "account not found";
        }
        if (!password_verify($current_password, $row['password'])) {
            return "incorrect current password";
        }
        if (password_verify($new_password, $row['password'])) {
            return "same as current password";
        }

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt2 = $this->connection->prepare("UPDATE track_acc SET password = ? WHERE id = ?");
        $stmt2->bind_param("si", $hashed, $acc_id);
        $result2 = $stmt2->execute();
        $stmt2->close();

        return $result2 ? true : "update failed";
    }
}