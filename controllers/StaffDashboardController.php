<?php
require_once "BaseController.php";

/**
 * Everything a logged-in staff/admin does from their dashboard:
 * viewing it, updating their own profile/password, approving or
 * rejecting orgs, managing staff accounts, and evaluating reports.
 */
class StaffDashboardController extends BaseController {

    public function __construct(){
        require_once "models/AuthModel.php";
        require_once "models/StaffModel.php";
        require_once "models/OrgModel.php";
        require_once "models/PermitApplicationModel.php";
        require_once "models/ApprovedPermitModel.php";
        require_once "models/AccomplishmentReportModel.php";
        require_once "controllers/LogControllers.php";

        $this->logControllers = new LogControllers();
        $this->authModel  = new AuthModel();
        $this->staffModel = new StaffModel();
        $this->orgModel   = new OrgModel();
        $this->applicationModel = new PermitApplicationModel();
        $this->approvedModel    = new ApprovedPermitModel();
        $this->reportModel      = new AccomplishmentReportModel();
    }

    public function dashboard_staff() {
        if (!isset($_SESSION['email']) || ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'admin')) {
            header("Location: signin");
            exit();
        }

        $user_email = $_SESSION['email'] ?? '';
        $staff_info = $user_email !== '' ? $this->staffModel->getStaffByEmail($user_email) : null;
        $password_change_success = $_SESSION['password_change_success'] ?? false;
        $password_error_message  = $_SESSION['password_change_error'] ?? '';
        $password_change_error   = $password_error_message !== '';
        unset($_SESSION['password_change_success'], $_SESSION['password_change_error']);

        if ($staff_info) {
            $user_id     = $staff_info['acc_id'];
            $first_name  = $staff_info['first_name'];
            $middle_name = $staff_info['middle_name'];
            $last_name   = $staff_info['last_name'];
            $contact_no  = $staff_info['contact_no'];
            $staff_role  = $staff_info['role'];
            $user_name   = trim($first_name . ' ' . $last_name);
        } else {
            $user_id     = null;
            $first_name  = '';
            $middle_name = '';
            $last_name   = '';
            $contact_no  = '';
            $staff_role  = 'staff';
            $user_name   = 'SOAU Staff';
        }
        $current_year = date('Y');

        // Profile update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
            $acc_id      = $_POST['acc_id'] ?? '';
            $first_name_input  = trim($_POST['first_name'] ?? '');
            $middle_name_input = trim($_POST['middle_name'] ?? '');
            $last_name_input   = trim($_POST['last_name'] ?? '');
            $email_input       = trim($_POST['email'] ?? '');
            $contact_no_input  = trim($_POST['contact_no'] ?? '');

            $namePattern         = '/^[A-Za-z\s\'\-]+$/';
            $namePatternOptional = '/^[A-Za-z\s\'\-]*$/'; // middle name may be blank

            if (!preg_match($namePattern, $first_name_input)
                || !preg_match($namePattern, $last_name_input)
                || !preg_match($namePatternOptional, $middle_name_input)
            ) {
                $_SESSION['profile_update_error'] = "Names may only contain letters, spaces, hyphens, and apostrophes.";
            } elseif (!preg_match('/^09\d{2}-\d{3}-\d{4}$/', $contact_no_input)) {
                $_SESSION['profile_update_error'] = "Contact number must be in the format 0911-111-1111.";
            } elseif (!str_ends_with($email_input, "@bsu.edu.ph")) {
                $_SESSION['profile_update_error'] = "Email must be a valid @bsu.edu.ph address.";
            } elseif ($this->staffModel->isEmailTakenByOther($email_input, $acc_id)) {
                $_SESSION['profile_update_error'] = "That email address is already in use by another account.";
            } else {
                $updated = $this->staffModel->updateStaffProfile(
                    $acc_id, $first_name_input, $middle_name_input, $last_name_input, $email_input, $contact_no_input
                );

                if ($updated) {
                    $_SESSION['email'] = $email_input;
                    $_SESSION['profile_update_message'] = "Staff profile updated successfully!";
                    logStaffAction("Updated Staff profile");
                } else {
                    $_SESSION['profile_update_error'] = "Failed to update profile. Please try again.";
                }
            }

            header("Location: dashboard_staff");
            exit();
        }

        // Password change
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'] ?? '';
            $new_password     = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            $isStrong = strlen($new_password) >= 8
                && preg_match('/[A-Z]/', $new_password)
                && preg_match('/[a-z]/', $new_password)
                && preg_match('/[0-9]/', $new_password)
                && preg_match('/[^A-Za-z0-9]/', $new_password);

            if (empty($current_password)) {
                $_SESSION['password_change_error'] = 'Please enter your current password.';
            } elseif (!$isStrong) {
                $_SESSION['password_change_error'] = 'New password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character.';
            } elseif ($new_password !== $confirm_password) {
                $_SESSION['password_change_error'] = 'New password and confirmation do not match.';
            } else {
                $result = $this->authModel->changePassword($user_id, $current_password, $new_password);
                if ($result === true) {
                    $_SESSION['password_change_success'] = true;
                    logStaffAction("Changed password");
                } elseif ($result === 'not logged in') {
                    $_SESSION['password_change_error'] = 'Your session has expired. Please log in again.';
                } elseif ($result === 'missing fields') {
                    $_SESSION['password_change_error'] = 'Please fill in all password fields.';
                } elseif ($result === 'weak password') {
                    $_SESSION['password_change_error'] = 'New password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character.';
                } elseif ($result === 'incorrect current password') {
                    $_SESSION['password_change_error'] = 'Current password is incorrect.';
                } elseif ($result === 'same as current password') {
                    $_SESSION['password_change_error'] = 'New password must be different from your current password.';
                } else {
                    $_SESSION['password_change_error'] = 'Something went wrong. Please try again.';
                }
            }

            header("Location: dashboard_staff");
            exit();
        }

        $profile_update_message = $_SESSION['profile_update_message'] ?? '';
        unset($_SESSION['profile_update_message']);

        $profile_update_error = $_SESSION['profile_update_error'] ?? '';
        unset($_SESSION['profile_update_error']);

        // Org approve/reject/update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['org_action'])) {
            $org_id   = $_POST['org_id'] ?? '';
            $org_name = trim($_POST['editable_org_name'] ?? $_POST['org_name'] ?? '');
            $email    = trim($_POST['editable_email'] ?? '');
            $action   = $_POST['action_type'] ?? '';
            $status   = $_POST['status_type'] ?? '';
            $remarks  = $_POST['remarks'] ?? '';

            if ($action === 'approve') {
                $this->orgModel->approveOrg($org_id, $status, $email, $org_name);
                $_SESSION['org_action_message'] = "Organization #$org_name has been approved as $status!";
                logStaffAction("Approved organization $org_name as $status");
            } elseif ($action === 'reject') {
                $this->orgModel->rejectOrg($org_id, $remarks);
                $_SESSION['org_action_message'] = "Organization #$org_name has been rejected. Reason: $remarks";
                logStaffAction("Rejected organization: $org_name. Reason: $remarks");
            } elseif ($action === 'update_status') {
                $this->orgModel->approveOrg($org_id, $status, $email, $org_name);
                $_SESSION['org_action_message'] = "Organization #$org_name status updated to $status!";
                logStaffAction("Updated organization $org_name status to $status");
            }

            header("Location: dashboard_staff");
            exit();
        }

        // Mass org renewal trigger
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger_renewal'])) {
            $updated = $this->orgModel->setAllOrgsToRenewal();

            if ($updated) {
                $_SESSION['org_action_message'] = "All organization statuses have been updated to 'Renewal'.";
                logStaffAction("Triggered mass renewal for all organization accounts.");
            } else {
                $_SESSION['org_action_message'] = "Failed to update organization statuses.";
            }

            header("Location: dashboard_staff");
            exit();
        }

        // Staff account activate/deactivate now lives at its own
        // action (staff_acc_management) — see that method below.

        $permit_action_message = $_SESSION['permit_action_message'] ?? '';
        unset($_SESSION['permit_action_message']);

        $org_action_message = $_SESSION['org_action_message'] ?? '';
        unset($_SESSION['org_action_message']);

        $pending_orgs_raw = $this->orgModel->getOrgsByStatus('Disabled');
        $pending_orgs = array_map(function ($org) {
            return [
                'acc_id'         => $org['acc_id'],
                'id'             => 'RSO-' . str_pad($org['acc_id'], 3, '0', STR_PAD_LEFT),
                'org_name'       => $org['org_name'],
                'email'          => $org['email'],
                'representative' => trim($org['first_name'] . ' ' . $org['middle_name'] . ' ' . $org['last_name']),
                'contact_no'     => $org['contact_no'],
                'type'           => $org['org_type'],
                'files'          => [
                    'LOA'   => $org['LOA'],
                    'AAF'   => $org['AAF'],
                    'POBIC' => $org['POBIC'],
                    'TAP'   => $org['TAP'],
                    'RCAB'  => $org['RCAB'],
                ]
            ];
        }, $pending_orgs_raw);

        $approved_orgs_raw = $this->orgModel->getOrgsByStatus('able');
        $approved_orgs = array_map(function ($org) {
            return [
                'acc_id'         => $org['acc_id'],
                'id'             => 'RSO-' . str_pad($org['acc_id'], 3, '0', STR_PAD_LEFT),
                'org_name'       => $org['org_name'],
                'email'          => $org['email'],
                'representative' => trim($org['first_name'] . ' ' . $org['last_name']),
                'contact_no'     => $org['contact_no'],
                'type'           => $org['org_type'],
                'status'         => $org['org_status'],
                'badge_color'    => 'approved',
                'LOA'            => $org['LOA'],
                'AAF'            => $org['AAF'],
                'POBIC'          => $org['POBIC'],
                'TAP'            => $org['TAP'],
                'RCAB'           => $org['RCAB'],
            ];
        }, $approved_orgs_raw);

        $staff_accounts_raw = $this->staffModel->getAllStaffAccounts();
        $staff_accounts = array_map(function ($staff) {
            return [
                'acc_id'      => $staff['acc_id'],
                'staff_id'    => $staff['staff_id'] ?? '',
                'id'          => 'STF-' . str_pad($staff['acc_id'], 3, '0', STR_PAD_LEFT),
                'first_name'  => $staff['first_name'],
                'last_name'   => $staff['last_name'],
                'full_name'   => trim($staff['first_name'] . ' ' . $staff['last_name']),
                'email'       => $staff['email'],
                'role'        => $staff['role'] ?? 'staff',
                'status'      => $staff['status'] ?? 'disabled',
                'badge_color' => ($staff['status'] ?? '') === 'able' ? 'approved' : 'rejected',
            ];
        }, $staff_accounts_raw);

        $approved_permits = $this->approvedModel->getAllApprovedPermits();
        $permits_raw      = $this->applicationModel->getAllPermitsByOrg();

        $applications = array_map(function ($permit) {
            return [
                'db_id'               => $permit['id'],
                'organization'        => $permit['org_name'],
                'campus_type_raw'     => $permit['campus_type'],
                'id'                  => $permit['pending_permit_no'] ?: ('PID-' . $permit['id']),
                'type'                => $permit['nature_of_activity'],
                'title'               => $permit['activity_title'],
                'date_submitted'      => $permit['start_date'],
                'status'              => $permit['status'],
                'campus_type'         => $permit['campus_type'] === 'on' ? 'On-Campus' : 'Off-Campus',
                'date_requested'      => $permit['start_date'],
                'venue'               => $permit['venue'],
                'remarks'             => 'Waiting for SOAU approval',
                'requirements_status' => 'not_submitted'
            ];
        }, $permits_raw);

        $completed_AR     = $this->reportModel->getAllSubmittedReports();
        $approved_AR      = $this->reportModel->getAllApprovedReports();
        $nature_breakdown = $this->approvedModel->getNatureOfActivityBreakdown();

        $logFilePath        = __DIR__ . '/../staff_audit_log.txt';
        $OrglogFilePath      = __DIR__ . '/../org_audit_log.txt';
        $PermitlogFilePath   = __DIR__ . '/../Other_log.txt';
        $Other_log = $this->logControllers->getParsedSystemLogs($PermitlogFilePath);
        $Org_logs  = $this->logControllers->getOrgParsedSystemLogs($OrglogFilePath);
        $Staff_logs = $this->logControllers->getParsedSystemLogs($logFilePath);

        include "views/dashboard_staff.php";
        exit();
    }

    /**
     * Activate/deactivate a staff account. Admin-only — enforced both here
     * (defense in depth) and by index.php's $admin_only_actions routing.
     */
    public function staff_acc_management() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            header("Location: dashboard_staff");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action_type'] ?? '') === 'update_status') {
            $staff_acc_id = $_POST['user_id'] ?? '';
            $new_status   = $_POST['status_type'] ?? 'disabled';

            if (!empty($staff_acc_id)) {
                $updated = $this->staffModel->updateStaffStatus($staff_acc_id, $new_status);

                if ($updated) {
                    $staff_member = $this->staffModel->getStaffByAccId($staff_acc_id);

                    if ($staff_member && !empty($staff_member['email'])) {
                        $full_name = trim(($staff_member['first_name'] ?? '') . ' ' . ($staff_member['last_name'] ?? ''));

                        try {
                            $mailResult = $this->staffModel->sendStaffStatusEmail($staff_member['email'], $full_name, $new_status);
                            if ($mailResult !== true) {
                                error_log("Failed to send status update email: " . $mailResult);
                            }
                        } catch (Exception $e) {
                            // Never let a mail failure block the status change from being reported.
                            error_log("sendStaffStatusEmail threw: " . $e->getMessage());
                        }
                    }

                    $action_label = ($new_status === 'able') ? 'activated' : 'deactivated';
                    $_SESSION['org_action_message'] = "Staff account has been {$action_label}.";
                    logStaffAction("Updated staff account #{$staff_acc_id} status to {$new_status}");
                } else {
                    $_SESSION['org_action_message'] = "Failed to update staff account status. Please try again.";
                    error_log("updateStaffStatus returned false for acc_id $staff_acc_id, status $new_status");
                }
            }
        }

        header("Location: dashboard_staff");
        exit();
    }

    public function clear_notif_logs() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'admin')) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $logFilePath = __DIR__ . '/../Other_log.txt';
        $cleared = $this->logControllers->clearSystemLogs($logFilePath);

        echo json_encode([
            'success' => $cleared,
            'message' => $cleared ? 'Logs cleared.' : 'Failed to clear log file.'
        ]);
        exit;
    }

    public function view_report() {
        $report_id = $_GET['id'] ?? null;

        if (!$report_id) {
            header("Location: dashboard_organization");
            exit();
        }

        $files = $this->reportModel->getAccomplishmentFiles($report_id);

        if (count($files) === 1) {
            header("Location: " . $files[0]['file_path']);
            exit();
        }

        include "views/view_accomplishment_files.php";
    }

    public function evaluate_report() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_report'])) {
            $report_id = !empty($_POST['report_id']) ? intval($_POST['report_id']) : 0;
            $permit_id = $_POST['permit_id'] ?? '';
            $rating    = floatval($_POST['rating'] ?? 0);
            $remarks   = trim($_POST['remarks'] ?? '');
            $title     = $_POST['activity_title'] ?? null;

            if (!empty($permit_id)) {
                $this->reportModel->updateReportRatingAndApprove($report_id, $permit_id, $rating, $remarks);
                logStaffAction("Evaluated report #$report_id for permit Title: $title with rating $rating");
            }

            header("Location: dashboard_staff");
            exit();
        }
    }
}