<?php
require_once "BaseController.php";

/**
 * Everything a logged-in organization does from their dashboard:
 * viewing it, updating profile/password, and submitting accomplishment
 * reports for approved permits.
 */
class OrgDashboardController extends BaseController {

    public function __construct(){
        require_once "models/AuthModel.php";
        require_once "models/OrgModel.php";
        require_once "models/PermitApplicationModel.php";
        require_once "models/ApprovedPermitModel.php";
        require_once "models/AccomplishmentReportModel.php";
        require_once "controllers/LogControllers.php";

        $this->logControllers = new LogControllers();
        $this->authModel = new AuthModel();
        $this->orgModel  = new OrgModel();
        $this->applicationModel = new PermitApplicationModel();
        $this->approvedModel    = new ApprovedPermitModel();
        $this->reportModel      = new AccomplishmentReportModel();
    }

    public function dashboard_organization() {
        if (!isset($_SESSION['email'])) {
            header("Location: signin");
            exit();
        }

        $permit_message = $_SESSION['permit_message'] = null;
        $password_change_success = $_SESSION['password_change_success'] ?? false;
        $password_error_message  = $_SESSION['password_change_error'] ?? '';
        $password_change_error   = $password_error_message !== '';
        unset($_SESSION['password_change_success'], $_SESSION['password_change_error']);

        // Requirements submission is actually handled by PermitFilesController
        // (see the submit_requirements / get_permit_files routes) — this just
        // logs the action and redirects.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_requirements'])) {
            $application_id = $_POST['application_id'] ?? '';
            $activity_title = $_POST['activity_title'] ?? '';
            logOrgAction("Submitted requirements for Activity Permit Title: $activity_title");
            OtherActions("Submitted requirements for Activity Permit Title: $activity_title");
            header("Location: dashboard_organization");
            exit();
        }

        // Accomplishment report submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_accomplishment'])) {
            $permit_id         = $_POST['permit_id'] ?? '';
            $activity_title    = $_POST['activity_title'] ?? '';
            $is_cancelled      = isset($_POST['is_cancelled']) && $_POST['is_cancelled'] === '1';
            $rating            = $is_cancelled ? 0.00 : (isset($_POST['rating']) ? floatval($_POST['rating']) : 0.00);
            $cancellation_reason = $is_cancelled ? trim($_POST['cancellation_reason'] ?? '') : null;

            if ($is_cancelled && $cancellation_reason === '') {
                $_SESSION['accomplishment_error'] = "Please provide a reason for the cancellation.";
                header("Location: dashboard_organization");
                exit();
            }

            $actual_submission = date('Y-m-d H:i:s');

            $report_id = $this->reportModel->createAccomplishmentReport($permit_id, $rating, $actual_submission);

            if ($report_id) {
                if ($is_cancelled) {
                    $this->reportModel->cancelPermitByGeneratedNo($permit_id, $cancellation_reason);
                }

                $upload_dir = 'uploads/accomplishment_reports/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Same whitelist approach as OrgModel::insertOrg()/RenewalOrg() —
                // extension must be on the list AND the file's real content
                // (via finfo, not the spoofable $_FILES['type']) must match it.
                $allowedTypes = [
                    'pdf'  => ['application/pdf'],
                    'doc'  => ['application/msword'],
                    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
                    'jpg'  => ['image/jpeg', 'image/pjpeg'],
                    'jpeg' => ['image/jpeg', 'image/pjpeg'],
                    'png'  => ['image/png'],
                ];

                $validateAccomplishmentUpload = function ($file) use ($allowedTypes) {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!array_key_exists($ext, $allowedTypes)) {
                        return false;
                    }
                    if (!is_uploaded_file($file['tmp_name'])) {
                        return false;
                    }
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $realMime = $finfo->file($file['tmp_name']);
                    return in_array($realMime, $allowedTypes[$ext], true);
                };

                if (isset($_FILES['accomplishment_report']) && $_FILES['accomplishment_report']['error'] === UPLOAD_ERR_OK) {
                    $file_data   = $_FILES['accomplishment_report'];
                    $orig_name   = $file_data['name'];
                    $filename    = pathinfo($orig_name, PATHINFO_FILENAME);
                    $ext         = pathinfo($orig_name, PATHINFO_EXTENSION);

                    if ($validateAccomplishmentUpload($file_data)) {
                        $clean_filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
                        $new_filename = $permit_id . '_' . $clean_filename . '.' . $ext;
                        $target_path  = $upload_dir . $new_filename;

                        if (move_uploaded_file($file_data['tmp_name'], $target_path)) {
                            $this->reportModel->saveAccomplishmentFile($report_id, 'accomplishment_report', $target_path, $orig_name);
                        }
                    } else {
                        $_SESSION['accomplishment_error'] = "'$orig_name' isn't an allowed file type (PDF, DOC, DOCX, JPG, PNG only) or its content didn't match its extension.";
                    }
                }

                if (isset($_FILES['others']) && is_array($_FILES['others']['name'])) {
                    $total_others = count($_FILES['others']['name']);

                    for ($i = 0; $i < $total_others; $i++) {
                        if ($_FILES['others']['error'][$i] === UPLOAD_ERR_OK) {
                            $orig_name = $_FILES['others']['name'][$i];
                            $tmp_name  = $_FILES['others']['tmp_name'][$i];
                            $filename  = pathinfo($orig_name, PATHINFO_FILENAME);
                            $ext       = pathinfo($orig_name, PATHINFO_EXTENSION);

                            $otherFile = ['name' => $orig_name, 'tmp_name' => $tmp_name];

                            if ($validateAccomplishmentUpload($otherFile)) {
                                $clean_filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
                                $new_filename = $permit_id . '_' . $clean_filename . '.' . $ext;
                                $target_path  = $upload_dir . $new_filename;

                                if (move_uploaded_file($tmp_name, $target_path)) {
                                    $this->reportModel->saveAccomplishmentFile($report_id, 'others', $target_path, $orig_name);
                                }
                            }
                        }
                    }
                }

                $_SESSION['accomplishment_message'] = $is_cancelled
                    ? "Activity #$permit_id marked as cancelled."
                    : "Accomplishment report for permit #$permit_id submitted successfully!";
                logOrgAction(($is_cancelled ? "Reported activity cancelled for " : "Submitted accomplishment report for permit ") . "Activity Title: $activity_title");
                OtherActions(($is_cancelled ? "Reported activity cancelled for " : "Submitted accomplishment report for permit ") . "Activity Title: $activity_title");
            } else {
                $_SESSION['accomplishment_error'] = "Failed to record accomplishment report. Please try again.";
            }

            header("Location: dashboard_organization");
            exit();
        }

        $accomplishment_message = $_SESSION['accomplishment_message'] ?? '';
        $accomplishment_error   = $_SESSION['accomplishment_error'] ?? '';
        unset($_SESSION['accomplishment_message'], $_SESSION['accomplishment_error']);

        $user_email = $_SESSION['email'] ?? '';
        $org_info   = $user_email !== '' ? $this->orgModel->getOrgByEmail($user_email) : null;

        if ($org_info) {
            $user_id    = $org_info['acc_id'];
            $first_name = $org_info['first_name'];
            $last_name  = $org_info['last_name'];
            $user_name  = trim($first_name . ' ' . $last_name);
            $org_name   = $org_info['org_name'];
            $org_status = $org_info['org_status'];
            $contact_no = $org_info['contact_no'] ?? '';
        } else {
            $user_id    = null;
            $first_name = '';
            $last_name  = '';
            $user_name  = 'Representative';
            $org_name   = 'Organization';
            $contact_no = '';
        }
        $current_year = date('Y');

        // Profile update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
            $acc_id     = $_POST['acc_id'] ?? '';
            $org_name   = trim($_POST['org_name'] ?? '');
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name  = trim($_POST['last_name'] ?? '');
            $contact_no = trim($_POST['contact_no'] ?? '');
            $email      = trim($_POST['email'] ?? '');

            $this->orgModel->updateOrgProfile($acc_id, $org_name, $first_name, $last_name, $contact_no, $email);
            $_SESSION['profile_update_message'] = "Organization profile updated successfully!";
            logOrgAction("Updated organization profile");

            header("Location: dashboard_organization");
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
                    logOrgAction("Changed password");
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

            header("Location: dashboard_organization");
            exit();
        }

        $profile_update_message = $_SESSION['profile_update_message'] ?? '';
        unset($_SESSION['profile_update_message']);

        $permits_raw  = $user_id ? $this->applicationModel->getPermitsByOrg($user_id) : [];
        $applications = array_map(function ($p) {
            return [
                'db_id'               => (int) $p['id'],
                'campus_type_raw'     => $p['campus_type'],
                'id'                  => $p['pending_permit_no'] ?: ('PID-' . $p['id']),
                'type'                => $p['nature_of_activity'],
                'title'               => $p['activity_title'],
                'date_submitted'      => $p['start_date'],
                'status'              => $p['status'],
                'campus_type'         => $p['campus_type'] === 'on' ? 'On-Campus' : 'Off-Campus',
                'date_requested'      => $p['start_date'],
                'remarks'             => 'Waiting for SOAU approval',
                'requirements_status' => $p['requirements_status'],
            ];
        }, $permits_raw);

        // Activity Permit Application tab only needs the ones still awaiting
        // requirements submission. Once requirements are in AND staff has
        // approved the permit, it moves to the Approved Permits tab instead.
        $pending_requirement_apps = array_values(array_filter($applications, function ($a) {
            return $a['requirements_status'] === 'pending';
        }));

        $approved_submitted_permits = array_values(array_filter($applications, function ($a) {
            return in_array($a['status'], ['approved', 'cancelled'], true) && $a['requirements_status'] === 'submitted';
        }));

        $completed_activities  = $user_id ? $this->approvedModel->getApprovedPermitsByOrg($user_id) : [];
        $approved_permits      = $this->approvedModel->getAllApprovedPermits();
        $submitted_reports_raw = $user_id ? $this->reportModel->getSubmittedReportsByOrg($user_id) : [];

        $submitted_reports = array_map(function($report) {
            $report['files'] = $this->reportModel->getAccomplishmentFiles($report['report_id']);
            return $report;
        }, $submitted_reports_raw);

        include "views/dashboard_rso.php";
        exit();
    }
}