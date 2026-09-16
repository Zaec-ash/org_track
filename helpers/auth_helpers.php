<?php
require_once "models/OrgModel.php";

/**
 * True if a user is currently signed in. UsersController::LogInUser()
 * sets both 'role' and 'acc_id' in the session on a successful login,
 * so requiring both is a decent guard against a half-populated session.
 */
function isLoggedIn() {
    return !empty($_SESSION['acc_id']) && !empty($_SESSION['role']);
}

/**
 * Blocks anonymous access. Call this (or requireRole()) at the top of
 * any action that should never be reachable while logged out. Logs the
 * blocked attempt before redirecting.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $attemptedAction = $_GET['url'] ?? $_REQUEST['action'] ?? 'unknown';
        logStaffAction("Access denied: anonymous request blocked from '$attemptedAction'");

        $_SESSION['login_required_message'] = "Please sign in to continue.";
        header("Location: signin");
        exit();
    }
}

/**
 * Blocks access unless the logged-in user's role is one of $allowedRoles
 * ('staff' or 'organization'). A logged-in user with the wrong role is
 * sent to their own dashboard rather than a hard error page — hitting
 * the other role's URL is almost always a stale link/bookmark, not an
 * attack, and there's no dedicated "no access" view in this app yet.
 * Logs the blocked attempt before redirecting.
 */
function requireRole($allowedRoles) {
    requireLogin();

    $allowedRoles = (array) $allowedRoles;
    if (!in_array($_SESSION['role'], $allowedRoles, true)) {
        $attemptedAction = $_GET['url'] ?? $_REQUEST['action'] ?? 'unknown';
        $who = $_SESSION['email'] ?? 'unknown user';
        logStaffAction("Access denied: '$who' (role: {$_SESSION['role']}) blocked from '$attemptedAction'");

        $ownDashboard = $_SESSION['role'] === 'staff' ? 'dashboard_staff' : 'dashboard_organization';
        header("Location: $ownDashboard");
        exit();
    }
}

function getAuthenticatedOrg() {
    $orgModel = new OrgModel();

    $client_email = $_SESSION['email'] ?? '';
    $org_info = $client_email !== '' ? $orgModel->getOrgByEmail($client_email) : null;

    if (!$org_info) {
        header("Location: signin");
        exit();
    }

    return $org_info;
}

function logStaffAction($actionDetails) {
    date_default_timezone_set('Asia/Manila');

    $logFile = 'staff_audit_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $userName = $_SESSION['email'] ?? 'Unknown Staff';
    $logEntry = "[$timestamp] $userName - $actionDetails" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Function to log organization actions. Writes to the same audit log as 
function logOrgAction($actionDetails) {
    date_default_timezone_set('Asia/Manila');
    $logFile = 'org_audit_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $userName = $_SESSION['email'] ?? 'Unknown Org';
    $logEntry = "[$timestamp] $userName - $actionDetails" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
function OtherActions($actionDetails) {
    date_default_timezone_set('Asia/Manila');
    $logFile = __DIR__ . '/../Other_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $userName = $_SESSION['email'] ?? '';
    $logEntry = "[$timestamp] $userName - $actionDetails" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}