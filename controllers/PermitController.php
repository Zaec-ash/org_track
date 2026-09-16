<?php

require_once "BaseController.php";
class   PermitController extends BaseController{
	public function __construct(){
      require_once "models/PermitApplicationModel.php";
      require_once "models/ApprovedPermitModel.php";
      require_once "models/OrgModel.php";
      $this->applicationModel = new PermitApplicationModel();
      $this->approvedModel = new ApprovedPermitModel();
      $this->orgModel = new OrgModel();
  }
	function permit_register(){
		if(!empty($_POST['submit_permit'])){
			// Trust the session, not the client — look up the logged-in org ourselves
			$org_info = $this->orgModel->getOrgByEmail($_SESSION['email'] ?? '');
			if (!$org_info) {
				header("Location: signin");
				exit();
			}
			$_POST['acc_id'] = $org_info['acc_id'];

			$result = $this->applicationModel->insertPermit($_POST);
            $activity_title    = $_POST['act_title'] ?? '';


			if ($result === true) {
    $permit_message = $_SESSION['permit_message'] = "Your activity permit application has been submitted successfully!";
    logOrgAction("Submitted $activity_title");
    OtherActions("Submitted $activity_title");
    header("Location: dashboard_organization");
} else {
    // TEMP debug — shows the real reason inline
    $_SESSION['permit_message'] = "Submission failed: " . (is_string($result) ? $result : 'unknown error');
    header("Location: registration");
}
exit();
		}
	}
    

function update_permit_registration(){

    $edit_permit = null;
    if (isset($_GET['id'], $_GET['type'])) {
        $edit_permit = $this->applicationModel->getPermitById($_GET['id'], $_GET['type']);
    }

    include "views/registration.php";
}
public function approve_permit() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['permit_id'], $_POST['campus_type'])) {
        $permit_id   = $_POST['permit_id'];
        $campus_type = $_POST['campus_type']; // 'on' or 'off'
        $report_due  = $_POST['report_due'] ?? null;
         $title = $_POST['activity_title'] ?? null;

        $result = $this->approvedModel->approvePermit($permit_id, $campus_type, $report_due);

        if ($result === true) {
            $_SESSION['permit_action_message'] = "Permit #$title has been approved successfully!";
             logStaffAction("approved Activity Permit Title: $title successfully!");
        } else {
            $_SESSION['permit_action_message'] = "Failed to approve permit: " . $result;
        }
    }

    header("Location: dashboard_staff");
    exit();
}
public function undo_approve_permit() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $permit_id = $_POST['permit_id'] ?? null;
        $campus_type = $_POST['campus_type'] ?? null;
        $activity_title = $_POST['activity_title'] ?? '';

        if ($permit_id && $campus_type) {
            $result = $this->approvedModel->undoApprovePermit($permit_id, $campus_type);

            if ($result === true) {
                $_SESSION['success'] = "Permit '{$activity_title}' has been moved back to Pending.";
            } else {
                $_SESSION['error'] = "Failed to revert permit: " . $result;
            }
        } else {
            $_SESSION['error'] = "Missing permit information.";
        }
    }

    header("Location: dashboard_staff");
    exit();
}
  public function clearAllApprovedPermits() {
    // 1. Clear any accidental whitespace or buffered HTML output
    while (ob_get_level()) {
        ob_end_clean();
    }

    // 2. Set JSON header
    header('Content-Type: application/json; charset=utf-8');

    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit();
        }

        // NOTE: removeAllApprovedPermitsAndFiles() does not exist on any model —
        // it was commented out in the original PermitsModel too, and this action
        // isn't routed in index.php, so it was already dead/unreachable code
        // before this refactor. Left as-is; implement on ApprovedPermitModel
        // before wiring an index.php route to this method.
        $result = $this->approvedModel->removeAllApprovedPermitsAndFiles();

        if ($result['success']) {
            if (function_exists('logStaffAction')) {
                logStaffAction("Purged all approved permits and associated files.");
            }
            echo json_encode(['success' => true, 'message' => 'All approved permits and files removed successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Database clearance failed.']);
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Server Exception: ' . $e->getMessage()]);
    }
    
    // 3. Force stop execution to prevent footer HTML rendering
    exit();
}
public function updatePermit() {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit();
    }

    $rawInput = file_get_contents('php://input');
    $data     = json_decode($rawInput, true);

    if (!$data || empty($data['permit_id'])) {
        echo json_encode(['success' => false, 'message' => 'Required permit identification data missing.']);
        exit();
    }

    // Bug fix: the original called updateInlinePermitData() twice in a row
    // (once into an unused $success, then again into $result) — meaning
    // every inline edit ran its DB transaction twice. Now called once.
    $result = $this->approvedModel->updateInlinePermitData($data);

    if ($result['success'] === true) {
        if (function_exists('logStaffAction')) {
            logStaffAction("Updated approved permit: " . $data['title']);
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Permit updated successfully.'
        ]);
    } else {
        // Output the actual error message caught during execution
        echo json_encode([
            'success' => false, 
            'message' => $result['error'] ?? 'Failed to save inline modifications to database.'
        ]);
    }
    exit();
}
    public function searchPermits() {
        header('Content-Type: application/json');
        $query = $_GET['q'] ?? null;
        // NOTE: getApprovedPermits($query) doesn't exist on any model (pre-existing
        // issue — this action also isn't routed in index.php). getAllApprovedPermits()
        // takes no search argument; add a real search method before using this.
        $permits = $this->approvedModel->getAllApprovedPermits();

        echo json_encode([
            'success' => true,
            'data'    => $permits
        ]);
        exit();
    }
// public function rejectRequirements() {
//     if (session_status() === PHP_SESSION_NONE) {
//         session_start();
//     }

//     // Role check: Ensure only authorized staff/admin users can perform rejections
//     $role = $_SESSION['role'] ?? null;
//     if (!$role || !in_array(strtolower($role), ['admin', 'staff', 'soau'])) {
//         $_SESSION['error_message'] = "Unauthorized access.";
//         header("Location: signin");
//         exit;

//     }

//     $permit_id = $_POST['permit_id'] ?? null;
//     $campus_type = $_POST['campus_type'] ?? null;
//     $rejection_reason = trim($_POST['rejection_reason'] ?? '');
//     $activity_title = $_POST['activity_title'] ?? '';
    
    

//     if (empty($permit_id) || empty($campus_type) || empty($rejection_reason)) {
//         $_SESSION['error_message'] = "Missing required fields for rejection.";
//         header("Location: dashboard_staff");
//         exit;
//     }
//     $this->model->deletePermitRequirementFiles($permit_id, $campus_type);
//     $this->model->sendRejectionEmail($permit_id, $campus_type, $rejection_reason);
//     // Automatically update requirement status from 'submitted' to 'resubmit'
//     $updated = $this->model->updateRejectRequirementsStatus($permit_id, $campus_type, 'resubmit');
//     logStaffAction("Reject Activity: $activity_title, Reason: $rejection_reason Status changed to Resubmit ");
    

//     if ($updated) {
//         $_SESSION['success_message'] = "Requirements rejected. Status changed to 'resubmit'.";
//     } else {
//         $_SESSION['error_message'] = "Failed to update requirement status in the database.";
//     }

//     header("Location: dashboard_staff");
//     exit;
// }
public function rejectRequirements() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Role check: Ensure only authorized staff/admin users can perform rejections
    $role = $_SESSION['role'] ?? null;
    if (!$role || !in_array(strtolower($role), ['admin', 'staff', 'soau'])) {
        $_SESSION['error_message'] = "Unauthorized access.";
        header("Location: signin");
        exit;
    }

    $permit_id = $_POST['permit_id'] ?? null;
    $campus_type = $_POST['campus_type'] ?? null;
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    $activity_title = $_POST['activity_title'] ?? '';

    if (empty($permit_id) || empty($campus_type) || empty($rejection_reason)) {
        $_SESSION['error_message'] = "Missing required fields for rejection.";
        header("Location: dashboard_staff");
        exit;
    }

    // 1. Send rejection notification email first
    $this->applicationModel->sendRejectionEmail($permit_id, $campus_type, $rejection_reason);

    // 2. Delete physical permit requirement files from storage
    $this->applicationModel->deletePermitRequirementFiles($permit_id, $campus_type);

    // 3. Delete the permit record completely from the database
    $deleted = $this->applicationModel->deletePermit($permit_id, $campus_type);

    // 4. Log staff action
    logStaffAction("Rejected and Deleted Activity Permit: '$activity_title', Reason: $rejection_reason");

    if ($deleted) {
        $_SESSION['success_message'] = "Permit '{$activity_title}' and its associated files have been permanently deleted.";
    } else {
        $_SESSION['error_message'] = "Failed to delete permit record from the database.";
    }

    header("Location: dashboard_staff");
    exit;
}
public function clearAndResetPermits() {
    // Prevent PHP errors/warnings from rendering HTML into the output stream
    ini_set('display_errors', '0');
    
    // Clear pre-existing output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit();
        }

        // NOTE: both of these were commented out in the original PermitsModel
        // and this action isn't routed in index.php — pre-existing dead code,
        // not something this refactor changes the behavior of.
        $clearResult = $this->approvedModel->removeAllApprovedPermitsAndFiles();
        $resetResult = $this->approvedModel->resetPermitSequence();

        if ($clearResult && $resetResult) {
            echo json_encode([
                'success' => true,
                'message' => 'Approved permits cleared and sequence counter reset to ' . date('m') . '-0001.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to process request.']);
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
    }

    exit(); // Always terminate script execution for JSON endpoints
}

}