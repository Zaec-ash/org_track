<?php
session_start();

// Root index.php
require_once 'controllers/AuthController.php';
require_once 'controllers/StaffDashboardController.php';
require_once 'controllers/OrgDashboardController.php';
require_once 'controllers/PermitFilesController.php';
require_once 'controllers/PermitController.php';
require_once 'helpers/auth_helpers.php';

// Initialize Controllers
$control_auth          = new AuthController();
$control_staff_dash    = new StaffDashboardController();
$control_org_dash      = new OrgDashboardController();
$control_permit        = new PermitController();
$control_permit_files  = new PermitFilesController();

// Get action from rewritten 'url' parameter or fallback to 'action'
$action = $_GET['url'] ?? $_REQUEST['action'] ?? 'home';
$action = rtrim($action, '/');

// --- Access Control -------------------------------------------------------

$guest_only_actions = ['home', 'signin'];

if (in_array($action, $guest_only_actions, true) && isLoggedIn()) {
    $ownDashboard = ($_SESSION['role'] === 'staff' || $_SESSION['role'] === 'admin') 
        ? 'dashboard_staff' 
        : 'dashboard_organization';
    header("Location: $ownDashboard");
    exit();
}

$staff_only_actions = [
    'dashboard_staff',
    'approve_permit',
    'evaluate_report',
    'undo_approve_permit',
    'get_report_files',
];

$admin_only_actions = [
    'staff_acc_management',
];

$org_only_actions = [
    'dashboard_organization',
    'permit_register',
    'update_permit_registration',
    'submit_requirements',
    'registration',
    'permit_registration',
];

$staff_or_org_actions = [
    'get_permit_files',
];

if (in_array($action, $staff_only_actions, true)) {
    requireRole(['staff', 'admin']);
} elseif (in_array($action, $admin_only_actions, true)) {
    requireRole('admin');
} elseif (in_array($action, $org_only_actions, true)) {
    requireRole('organization');
} elseif (in_array($action, $staff_or_org_actions, true)) {
    requireRole(['staff', 'admin', 'organization']);
}
// ---------------------------------------------------------------------------

switch($action) {
    case 'home':
        $control_auth->home();
        break;
    case 'signin':
        $control_auth->signin();
        break;
    case 'LogOut':
    case 'logout':
        $control_auth->logout();
        break;
    case 'register':
        $control_auth->register();
        break;
    case 'org_registration':
        $control_auth->org_registration();
        break;
    case 'staff_registration':
        $control_auth->staff_registration();
        break;
    case 'register_staff':
        $control_auth->register_staff();
        break;
    case 'registration':
        $control_auth->registration();
        break;
    case 'LogInUser':
        $control_auth->LogInUser();
        break;
    case 'dashboard_staff':
        $control_staff_dash->dashboard_staff();
        break;
    case 'dashboard_organization':
        $control_org_dash->dashboard_organization();
        break;
    case 'permit_registration':
        $control_auth->permit_site();
        break;
    case 'permit_register':
        $control_permit->permit_register();
        break;
    case 'viewing':
        $control_auth->interactive_veiwing();
        break;
    case 'update_permit_registration':
        $control_permit->update_permit_registration();
        break;
    case 'submit_requirements':          
        $control_permit_files->submitRequirements();
        break;
    case 'get_permit_files': 
        $control_permit_files->getFilesJson();
        break;
    case 'approve_permit':
        $control_permit->approve_permit();
        break;
    case 'undo_approve_permit':     
        $control_permit->undo_approve_permit();
        break;
    case 'update_permit':
        $control_permit->updatePermit();
        break;
    case 'clear_notif_logs':
        $control_staff_dash->clear_notif_logs();
        break;
    case 'get_report_files':
        $control_permit->get_report_files();
        break;
    case 'evaluate_report':
        $control_staff_dash->evaluate_report();
        break;
    case 'staff_acc_management':
        $control_staff_dash->staff_acc_management();
        break;
    case 'reject_requirements':
        $control_permit->rejectRequirements();
        break;
    case '404':
        include "views/404.php";
        break;
    default:
        $control_auth->home();
        break;
}