<?php
$org_action_message = '';


// Handle permit approval/rejection
$permit_action_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['permit_action'])) {
    $permit_id      = $_POST['permit_id'] ?? '';
    $activity_title = $_POST['activity_title'] ?? '';
    $action         = $_POST['action_type'] ?? '';
    $remarks        = $_POST['remarks'] ?? '';
    
    if ($action === 'approve') {
        $permit_action_message = "Activity \"$activity_title\" has been approved!";
        logStaffAction("Approved activity: $activity_title");
    } elseif ($action === 'reject') {
        $permit_action_message = "Activity \"$activity_title\" has been rejected. Reason: $remarks";
        logStaffAction("Rejected activity: $activity_title. Reason: $remarks");
    }
}

// Handle report generation
$selected_type = $_POST['report_type'] ?? '';
$report_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $selected_type = $_POST['report_type'] ?? '';
    $report_result = [
        'type' => $selected_type,
        'count' => rand(5, 25),
        'percentage' => rand(10, 90)
    ];
    logStaffAction("Generated report for activity type: $selected_type");
}

// Calculate statistics from data arrays
$total_org = count($pending_orgs) + count($approved_orgs);
$pending_org = count($pending_orgs);
$approved_org = count($approved_orgs);
$pending_AR = count($completed_AR);
$approved_AR = count($approved_AR);
$total_permits = count($approved_permits);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOAU Staff Dashboard | BSU ORG-Track</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="design/dash_rso.css" rel="stylesheet">
    <link href="design/dash_staff.css" rel="stylesheet">
    <link href="design/enhancements.css" rel="stylesheet">
    
<body>
    <!-- Toast container -->
    <div id="toastContainer" class="toast-container"></div>

        <?php include "views/partials/nav_dash.php"; ?>

<!-- Mobile sidebar toggle + overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="dashboard-layout">
    <!-- Vertical Sidebar Tabs with Logout at Bottom -->
    <div class="vertical-tabs" id="verticalTabs">
        <button class="vertical-tab active" onclick="switchTab('dashboard')">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </button>
        <button class="vertical-tab" onclick="switchTab('approve_org')">
            <i class="fas fa-building"></i> Organizations Account
        </button>
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
         <button class="vertical-tab" onclick="switchTab('Staff_account')">
            <i class="fas fa-building"></i> Staff Accounts
        </button>
        <?php endif; ?>
        <button class="vertical-tab" onclick="switchTab('pending_permits')">
            <i class="fas fa-clock"></i> Pending Permits
        </button>
        <button class="vertical-tab" onclick="switchTab('pending_permitsv2')">
            <i class="fas fa-clock"></i> Approved Permits
        </button>
        <button class="vertical-tab" onclick="switchTab('evaluate_reports')">
            <i class="fas fa-chart-line"></i> Accomplishment Reports
        </button>
        <button class="vertical-tab" onclick="switchTab('approved_permits')">
            <i class="fas fa-check-circle"></i> Permit List
        </button>
        <button class="vertical-tab" onclick="switchTab('system_logs')">
            <i class="fas fa-server"></i> Staff Logs
        </button>
        <button class="vertical-tab" onclick="switchTab('Organization_log')">
            <i class="fas fa-history"></i> Organization Logs
        </button>
    </div>

    <div class="main-content">
        <!-- DASHBOARD TAB -->
        <div id="dashboard" class="tab-content active">
            <div class="welcome-header">
                <div class="welcome-text">
                    <h2><i class=""></i> Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
                    <p>SOAU Staff Dashboard - Manage organizations, activity permits, and monitor system logs</p>
                    <span class="role-badge"><i class="fas fa-user-shield"></i> SOAU Staff</span>
                </div>
                <div>
                    <i class="fas fa-leaf" style="color: white; font-size: 42px; opacity: 0.3;"></i>
                </div>
            </div>
            <div class="stat-card total">
            <div class="stat-label" style="font-weight: bold; font-size: 1.1rem; margin-bottom: 12px;">
        <i class=""></i> Dashboard Overview
    </div>
    
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px; width: 100%;">
        <div onclick="switchTab('approve_org')" style="cursor: pointer; text-align: center; flex: 1;">
            <div class="stat-value" data-target="<?php echo (int)$total_org; ?>">0</div>
            <div class="stat-label"></i> Total Organization</div>
        </div>

        <div onclick="switchTab('approve_org')" style="cursor: pointer; text-align: center; flex: 1;">
            <div class="stat-value" data-target="<?php echo (int)$approved_org; ?>">0</div>
            <div class="stat-label"></i> Approved Organization</div>
        </div>

  <div onclick="openNatureBreakdownModal()" style="cursor: pointer; text-align: center; flex: 1;">
    <div class="stat-value" data-target="<?php echo (int)$total_permits; ?>">0</div>
    <div class="stat-label"><i class=""></i> Total Approved Permit</div>
</div>

        <div onclick="switchTab('evaluate_reports')" style="cursor: pointer; text-align: center; flex: 1;">
            <div class="stat-value" data-target="<?php echo (int)$pending_AR; ?>">0</div>
            <div class="stat-label"></i> Pending Reports</div>
        </div>

        <div onclick="" style="cursor: pointer; text-align: center; flex: 1;">
            <div class="stat-value" data-target="<?php echo (int)$approved_AR; ?>">0</div>
            <div class="stat-label"></i> Approved Reports</div>
        </div>

        
    </div>
</div>
            <!-- <div class="info-card">
                <h3 class="section-title"><i class="fas fa-chart-bar"></i> Activity Report Generator</h3>
                
                <form method="POST" class="report-form">
                    <label style="font-weight: 600;"><i class="fas fa-tag"></i> Select Activity Type:</label>
                    <select name="report_type" required>
                        <option value="" disabled selected>Select Nature of Activity...</option>
                        <option value="Meeting/Fellowship">Meeting/Fellowship</option>
                        <option value="Maintenance/Cleaning">Maintenance/Cleaning</option>
                        <option value="Seminar/Training/Forum">Seminar/Training/Forum</option>
                        <option value="Socialization">Socialization</option>
                        <option value="Contest/Competition">Contest/Competition</option>
                        <option value="Extension/Outreach">Extension/Outreach</option>
                        <option value="Campaign/Recruitment">Campaign/Recruitment</option>
                        <option value="Income Generating Activity">Income Generating Activity</option>
                        <option value="Collection of Fees/Fines">Collection of Fees/Fines</option>
                        <option value="Others">Others</option>
                    </select>
                    <button type="submit" name="generate_report" class="report-btn" onclick="return handleFormConfirm(event, 'Generate this report?', {okClass:'btn-approve', okText:'Generate'})"><i class="fas fa-download"></i> Generate Report →</button>
                </form>

                <?php if ($report_result): ?>
                <div style="margin-top: 20px; padding: 18px; background: var(--bsu-mint); border-radius: 18px;">
                    <h4><i class="fas fa-file-alt"></i> Report Summary: <?php echo $report_result['type']; ?></h4>
                    <p>Total Activities: <strong><?php echo $report_result['count']; ?></strong></p>
                    <p>Percentage of Total: <strong><?php echo $report_result['percentage']; ?>%</strong></p>
                    <button class="btn-sm btn-view" onclick="downloadReport()"><i class="fas fa-print"></i> Download Report (PDF)</button>
                </div>
                <?php endif; ?>
            </div> -->
        </div>
 <!-- TAB: Organization Profile -->
        <div id="profile" class="tab-content">
    <div class="info-card">
        <h3 class="section-title">
            <i class="fas fa-building"></i> Staff Profile
        </h3>

        <?php if ($profile_update_message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($profile_update_message); ?></div>
        <?php endif; ?>
        <?php if ($profile_update_error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($profile_update_error); ?></div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validateStaffProfileForm(this)">
            <input type="hidden" name="acc_id" value="<?php echo htmlspecialchars($user_id); ?>">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Email Address</span>
                    <input type="email" name="email" class="profile-input" value="<?php echo htmlspecialchars($user_email); ?>" required pattern=".+@bsu\.edu\.ph" title="Must end with @bsu.edu.ph">
                </div>
                <div class="info-item">
                    <span class="info-label">First Name</span>
                    <input type="text" name="first_name" id="profile_first_name" class="profile-input" value="<?php echo htmlspecialchars($first_name); ?>" required pattern="[A-Za-z\s'\-]+" title="Letters only" oninput="restrictToLetters(this)">
                </div>
                <div class="info-item">
                    <span class="info-label">Middle Name</span>
                    <input type="text" name="middle_name" id="profile_middle_name" class="profile-input" value="<?php echo htmlspecialchars($middle_name ?? ''); ?>" placeholder="Optional" pattern="[A-Za-z\s'\-]*" title="Letters only" oninput="restrictToLetters(this)">
                </div>
                <div class="info-item">
                    <span class="info-label">Last Name</span>
                    <input type="text" name="last_name" id="profile_last_name" class="profile-input" value="<?php echo htmlspecialchars($last_name); ?>" required pattern="[A-Za-z\s'\-]+" title="Letters only" oninput="restrictToLetters(this)">
                </div>
                <div class="info-item">
                    <span class="info-label">Contact Number</span>
                    <input type="text" name="contact_no" id="profile_contact_no" class="profile-input" value="<?php echo htmlspecialchars($contact_no ?? ''); ?>" required inputmode="numeric" maxlength="13" title="Format: 0911-111-1111" oninput="formatContactNumber(this)">
                </div>
                <div class="info-item">
                    <span class="info-label">Role</span>
                    <span class="info-value"><i class="fas fa-trophy"></i> <?php echo htmlspecialchars(ucfirst($staff_role ?? 'staff')); ?></span>
                </div>
            </div>
            <div style="margin-top: 24px; text-align: right;">
                <button type="submit" name="update_profile" class="btn-primary"><i class="fas fa-save"></i> Update Profile</button>
            </div>
        </form>
    </div>
                <div class="info-card">
                <h3 class="section-title">
                    <i class="fas fa-key"></i> Change Password
                </h3>
                
                <?php if ($password_change_success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Password changed successfully! Your new password has been saved.
                    </div>
                <?php endif; ?>
                
                <?php if ($password_change_error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $password_error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="password-form" onsubmit="return validateStaffPasswordForm(this)">
                    <div class="form-group">
                        <label name="curpass"><i class="fas fa-lock"></i> Current Password</label>
                        <input type="password" name="current_password" placeholder="Enter your current password" required>
                    </div>
                    <div class="form-group">
                        <label name="newpass"><i class="fas fa-key"></i> New Password</label>
                        <input type="password" name="new_password" id="new_password" placeholder="Enter new password (min. 6 characters)" required onkeyup="checkPasswordStrength()">
                        <div class="password-strength" id="strengthMessage"></div>
                    </div>
                    <div class="form-group">
                        <label name="conpass"><i class="fas fa-check-circle"></i> Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your new password" required onkeyup="checkPasswordMatch()">
                        <div class="password-strength" id="matchMessage"></div>
                    </div>
                    <div style="margin-top: 24px; text-align: right;">
                    <button type="submit" name="change_password" class="btn-primary"><i class="fas fa-save"></i> Update Password</button>
                </div>
                </form>
            </div>
</div>
        <!-- TAB 1: Approve Organizations -->
        <div id="approve_org" class="tab-content">
            <?php if ($org_action_message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $org_action_message; ?></div>
            <?php endif; ?>
            
            <!-- Pending Organizations Section -->
            <div class="table-card">
                <div style="padding: 20px 20px 0 20px;">
                    <h3 class="section-title"></i> Pending Organization Approvals</h3>
                </div>
                <div style="overflow-x: auto;">
                <table class="data-table" id="pendingOrgsTable">
                    <thead>
                        <tr><th>ID</th><th>Organization Name</th><th>Email</th><th>Representative</th><th>contact number</th><th>Type</th><th>Files</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
    <?php if (!empty($pending_orgs)): ?>
    <?php foreach ($pending_orgs as $org): ?>
    <tr>
        <td><strong><?php echo $org['id']; ?></strong></td>
        
        <!-- EDITABLE ORG NAME & EMAIL -->
        <td>
            <input type="text" 
                   name="editable_org_name" 
                   value="<?php echo htmlspecialchars($org['org_name']); ?>" 
                   form="form-approve-<?php echo $org['acc_id']; ?>" 
                   class="table-input" 
                   required>
        </td>
        <td>
            <input type="email" 
                   name="editable_email" 
                   value="<?php echo htmlspecialchars($org['email']); ?>" 
                   form="form-approve-<?php echo $org['acc_id']; ?>" 
                   class="table-input" 
                   required>
        </td>

        <td><?php echo htmlspecialchars($org['representative']); ?></td>
        <td><?php echo htmlspecialchars($org['contact_no']); ?></td>
        <td><span class="status-badge status-pending"><?php echo ucfirst($org['type']); ?></span></td>
        <td>
            <button class="btn-sm btn-view" onclick="viewOrgFiles('<?php echo $org['id']; ?>', '<?php echo $org['type']; ?>')">
                View Files
            </button>
        </td>
        <td>
            <form id="form-approve-<?php echo $org['acc_id']; ?>" method="POST" style="display: inline-block;">
                <input type="hidden" name="org_id" value="<?php echo $org['acc_id']; ?>">
                <input type="hidden" name="action_type" value="approve">
                <select name="status_type" class="status-select" required style="margin-right: 5px;">
                    <option value="Probationary">Probationary</option>
                    <option value="Conditional">Conditional</option>
                    <option value="Full Recognition">Full Recognition</option>
                </select></td><td>
                <button type="submit" name="org_action" class="btn-sm btn-approve" onclick="return handleFormConfirm(event, 'Approve this organization?', {okClass:'btn-approve', okText:'Yes, Approve'})">
                    <i class="fas fa-check"></i> Approve
                </button>
            </form></td>
            <td>
            <button class="btn-sm btn-reject" onclick="showOrgRejectModal('<?php echo $org['acc_id']; ?>', '<?php echo htmlspecialchars($org['org_name']); ?>')">
                <i class="fas fa-times"></i> Reject
            </button>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php else: ?>
    <tr>
        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 20px;">
            No Organization found.
        </td>
    </tr>
    <?php endif; ?>
</tbody>
                </table>
                </div>
                <div class="log-pagination" style="display: flex; justify-content: center; align-items: center; gap: 12px; padding: 16px 20px;">
                    <button type="button" class="btn-sm btn-outline" id="pendingOrgsTable_prevBtn"><i class="fas fa-chevron-left"></i> Back</button>
                    <span id="pendingOrgsTable_pageInfo" style="font-size: 0.8rem; color: var(--text-muted);">Page 1 of 1</span>
                    <button type="button" class="btn-sm btn-outline" id="pendingOrgsTable_nextBtn">Next <i class="fas fa-chevron-right"></i></button>
                </div>
            </div>

            <!-- Approved Organizations Section -->
            <div class="table-card">
                <div style="padding: 20px 20px 0 20px;">
                    <h3 class="section-title"><i class="fas fa-check-circle"></i> Approved Organizations
                    <form method="POST" style="display: inline-block;">
                    
                                </form></h3>
                </div> 
                <div style="overflow-x: auto;">
                <table class="data-table" id="approvedOrgsTable">
                    <thead>
                        <tr><th>ID</th><th>Organization Name</th><th>Email</th><th>Officer</th><th>Contact Number</th><th>RSO Status</th><th>Files</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                         <?php if (!empty($approved_orgs)): ?>
                        <?php foreach ($approved_orgs as $org): ?>
                        <tr>
    <td><strong><?php echo $org['id']; ?></strong></td>
    <td>
        <input type="text"
               name="editable_org_name"
               value="<?php echo htmlspecialchars($org['org_name']); ?>"
               form="form-update-<?php echo $org['acc_id']; ?>"
               class="table-input"
               required>
    </td>
    <td>
        <input type="email"
               name="editable_email"
               value="<?php echo htmlspecialchars($org['email']); ?>"
               form="form-update-<?php echo $org['acc_id']; ?>"
               class="table-input"
               required>
    </td>

    <td><?php echo htmlspecialchars($org['representative']); ?></td>
    <td><?php echo htmlspecialchars($org['contact_no']); ?></td>
    <td>
        <span class="status-badge status-<?php echo $org['badge_color']; ?>">
            <?php echo $org['status']; ?>
        </span>
    </td>
    <td><button class="btn-sm btn-view" onclick="viewOrgFiles('<?php echo $org['id']; ?>', '<?php echo $org['type']; ?>')"> View Files</button></td>
    <td>
        <form id="form-update-<?php echo $org['acc_id']; ?>" method="POST" style="display: inline-block;">
            <input type="hidden" name="org_id" value="<?php echo $org['acc_id']; ?>">
            <input type="hidden" name="action_type" value="update_status">
            <select name="status_type" class="status-select" required>
                <option value="Probationary" <?php echo $org['status'] == 'Probationary' ? 'selected' : ''; ?>>Probationary</option>
                <option value="Conditional" <?php echo $org['status'] == 'Conditional' ? 'selected' : ''; ?>>Conditional</option>
                <option value="Full Recognition" <?php echo $org['status'] == 'Full Recognition' ? 'selected' : ''; ?>>Full Recognition</option>
            </select>
                        </td>
                        <td>
            <button type="submit" name="org_action" class="btn-sm btn-update" onclick="return handleFormConfirm(event, 'Update this organization\'s status?', {okClass:'btn-approve', okText:'Yes, Update'})"><i class="fas fa-sync-alt"></i> Update</button>
            
        </form></td>
</tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="17" style="text-align: center; color: var(--text-muted); padding: 20px;">
                                No Organization found.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <div class="log-pagination" style="display: flex; justify-content: center; align-items: center; gap: 12px; padding: 16px 20px;">
                    <button type="button" class="btn-sm btn-outline" id="approvedOrgsTable_prevBtn"><i class="fas fa-chevron-left"></i> Back</button>
                    <span id="approvedOrgsTable_pageInfo" style="font-size: 0.8rem; color: var(--text-muted);">Page 1 of 1</span>
                    <button type="button" class="btn-sm btn-outline" id="approvedOrgsTable_nextBtn">Next <i class="fas fa-chevron-right"></i></button>
                </div>
                        </div>
                       <div style="margin-top: 16px; display: flex; justify-content: flex-end; width: 100%;">
    <form method="POST" style="margin: 0;">
        <input type="hidden" name="trigger_renewal" value="1">
        <button 
            type="submit" 
            name="org_renewal" 
            style="
                background: linear-gradient(135deg, #2d6a4f, #1b4332);
                color: #ffffff;
                border: none;
                border-radius: 10px;
                padding: 14px 28px;
                font-size: 1rem;
                font-weight: 600;
                letter-spacing: 0.3px;
                display: inline-flex;
                align-items: center;
                gap: 10px;
                cursor: pointer;
                box-shadow: 0 4px 14px rgba(45, 106, 79, 0.4);
                transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.2s ease;
            "
            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 18px rgba(45, 106, 79, 0.5)'; this.style.background='linear-gradient(135deg, #1b4332, #143527)';"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 14px rgba(45, 106, 79, 0.4)'; this.style.background='linear-gradient(135deg, #2d6a4f, #1b4332)';"
            onclick="return handleFormConfirm(event, 'Initiate annual organization renewal?', {okClass:'btn-approve', okText:'Yes, Renew'})"
        >
            <i class="fas fa-redo-alt" style="font-size: 1.1rem;"></i>
            <span>Process Renewal</span>
        </button>
    </form>
</div>
</div>

        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <div id="Staff_account" class="tab-content">
            <?php if ($org_action_message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $org_action_message; ?></div>
            <?php endif; ?>
            
            <div class="table-card">
    <div style="padding: 20px 20px 0 20px;">
        <h3 class="section-title"><i class="fas fa-user-shield"></i> Staff Accounts</h3>
    </div>
    <div style="overflow-x: auto;">
        <table class="data-table" id="staffAccountsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contact Number</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                 <?php if (!empty($staff_accounts_raw)): ?>
                        
                <?php foreach ($staff_accounts_raw as $staff): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars('STF-' . str_pad($staff['acc_id'], 3, '0', STR_PAD_LEFT)); ?></strong></td>
                    <td><?php echo htmlspecialchars(trim($staff['first_name'] . ' ' . $staff['last_name'])); ?></td>
                    <td><?php echo htmlspecialchars($staff['email']); ?></td>
                    <td><?php echo htmlspecialchars($staff['contact_no'] ?? 'N/A'); ?></td>
                    <td><span class="status-badge"><?php echo ucfirst(htmlspecialchars($staff['role'] ?? 'Staff')); ?></span></td>
                    <td>
                        <?php if (($staff['status'] ?? '') === 'able'): ?>
                            <span class="status-badge status-approved">Active</span>
                        <?php else: ?>
                            <span class="status-badge status-rejected">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (($staff['status'] ?? '') === 'able'): ?>
                            <!-- Deactivate / Disable Form -->
                            <form action="staff_acc_management" method="POST" style="display: inline-block;">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($staff['acc_id']); ?>">
                                <input type="hidden" name="action_type" value="update_status">
                                <input type="hidden" name="status_type" value="disabled">
                                <button type="submit" name="staff_status_action" class="btn-sm btn-reject" onclick="return handleFormConfirm(event, 'Deactivate this staff account?', {okClass:'btn-reject', okText:'Deactivate'})">
                                    <i class="fas fa-user-slash"></i> Disable
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- Activate / Enable Form -->
                            <form action="staff_acc_management" method="POST" style="display: inline-block;">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($staff['acc_id']); ?>">
                                <input type="hidden" name="action_type" value="update_status">
                                <input type="hidden" name="status_type" value="able">
                                <button type="submit" name="staff_status_action" class="btn-sm btn-approve" onclick="return handleFormConfirm(event, 'Activate this staff account?', {okClass:'btn-approve', okText:'Activate'})">
                                    <i class="fas fa-user-check"></i> Activate
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="17" style="text-align: center; color: var(--text-muted); padding: 20px;">
                                No Staff found.
                            </td>
                        </tr>
                    <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="log-pagination" style="display: flex; justify-content: center; align-items: center; gap: 12px; padding: 16px 20px;">
        <button type="button" class="btn-sm btn-outline" id="staffAccountsTable_prevBtn"><i class="fas fa-chevron-left"></i> Back</button>
        <span id="staffAccountsTable_pageInfo" style="font-size: 0.8rem; color: var(--text-muted);">Page 1 of 1</span>
        <button type="button" class="btn-sm btn-outline" id="staffAccountsTable_nextBtn">Next <i class="fas fa-chevron-right"></i></button>
    </div>
</div></div>
        <?php endif; ?>
       

        <!-- TAB 2: Pending Activity Permits -->
        <div id="pending_permits" class="tab-content">
            <?php if ($permit_action_message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $permit_action_message; ?></div>
            <?php endif; ?>
               <!-- Pending Activity Permits -->
<div class="table-card">
    <div style="padding: 20px 20px 0 20px;">
        <h3 class="section-title"><i class="fas fa-clock"></i> Pending Activity Permits</h3>
    </div>
    <div style="overflow-x: auto;">
        <table class="data-table" id="pendingPermitsTable">
            <thead>
                <tr><th>ID</th><th>Organization</th><th>Activity Title</th><th>Type</th><th>Date</th><th>Venue</th><th>Files</th><th>Action</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (!empty($applications)): ?>
                <?php foreach ($applications as $permit): ?>
                <tr>
                    <td><strong><?php echo $permit['id']; ?></strong></td>
                    <td><?php echo $permit['organization']; ?></td>
                    <td><?php echo $permit['title']; ?></td>
                    <td><?php echo $permit['type']; ?></td>
                    <td><?php echo date('M d, Y', strtotime($permit['date_submitted'])); ?></td>
                    <td><?php echo $permit['venue']; ?></td>
                    <td>
                        <button class="btn-sm btn-view" onclick="viewPermitFiles('<?php echo $permit['db_id']; ?>', '<?php echo $permit['campus_type_raw'] ?? 'on'; ?>', '<?php echo htmlspecialchars($permit['id']); ?>')">
                            <i class="fas fa-folder-open"></i> View Files
                        </button>
                    </td>
                    <td>
                        <form action="approve_permit" method="POST" style="display: inline-block;" class="approve-permit-form">
                            <input type="hidden" name="permit_id" value="<?php echo $permit['db_id']; ?>">
                            <input type="hidden" name="campus_type" value="<?php echo $permit['campus_type_raw']; ?>">
                            <input type="hidden" name="activity_title" value="<?php echo htmlspecialchars($permit['title']); ?>">
                            <button type="submit" class="btn-sm btn-approve">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        </form></td><td>
                        <button class="btn-sm btn-reject" onclick="showPermitRejectModal('<?php echo $permit['db_id']; ?>', '<?php echo $permit['campus_type_raw'] ?? 'on'; ?>', '<?php echo htmlspecialchars($permit['title']); ?>')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 20px;">
                        No pending permits found.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="log-pagination" style="display: flex; justify-content: center; align-items: center; gap: 12px; padding: 16px 20px;">
        <button type="button" class="btn-sm btn-outline" id="pendingPermitsTable_prevBtn"><i class="fas fa-chevron-left"></i> Back</button>
        <span id="pendingPermitsTable_pageInfo" style="font-size: 0.8rem; color: var(--text-muted);">Page 1 of 1</span>
        <button type="button" class="btn-sm btn-outline" id="pendingPermitsTable_nextBtn">Next <i class="fas fa-chevron-right"></i></button>
    </div>
</div></div>

<!-- Approved Activity Permits -->
 <div id="pending_permitsv2" class="tab-content">
<div class="table-card">
    <div style="padding: 20px 20px 0 20px;">
        <h3 class="section-title"><i class="fas fa-check-circle"></i> Approved Activity Permits</h3>
    </div>
    <div style="overflow-x: auto;">
        <table class="data-table" id="approvedActivityPermitsTable">
            <thead>
                <tr><th>Permit ID</th><th>Organization</th><th>Activity Title</th><th>Type</th><th>Start Date</th><th>End Date</th><th>Venue</th><th>Approval Date</th><th>Status</th><th>Action</th></tr>
            </thead>
           <tbody>
    <?php if (!empty($approved_permits)): ?>
        <?php foreach ($approved_permits as $permit): ?>
        <tr>
            <td><strong><?php echo htmlspecialchars($permit['permit_id'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
            <td><?php echo htmlspecialchars($permit['organization'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($permit['activity_title'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($permit['type'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($permit['start_date'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($permit['end_date'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($permit['venue'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo date('M d, Y', strtotime($permit['approval_date'])); ?></td>
            <td><span class="status-badge status-approved">Approved</span></td>
            <td>
    <form action="index.php?action=undo_approve_permit" method="POST" style="display: inline-block;" onsubmit="return handleFormConfirm(event, 'Are you sure you want to reject this permit?', {okClass:'btn-reject', okText:'Yes, Reject'})">
        <input type="hidden" name="permit_id" value="<?php echo htmlspecialchars($permit['permit_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="campus_type" value="<?php echo htmlspecialchars($permit['campus_type'] ?? $permit['permit_type'] ?? $permit['type'] ?? 'on', ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="activity_title" value="<?php echo htmlspecialchars($permit['activity_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        
        <button type="submit" class="btn-sm btn-update">
            <i class="fas fa-redo"></i> Undo
        </button>
    </form>
</td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 20px;">
                No approved permits found.
            </td>
        </tr>
    <?php endif; ?>
</tbody>
        </table>
    </div>
    <div class="log-pagination" style="display: flex; justify-content: center; align-items: center; gap: 12px; padding: 16px 20px;">
        <button type="button" class="btn-sm btn-outline" id="approvedActivityPermitsTable_prevBtn"><i class="fas fa-chevron-left"></i> Back</button>
        <span id="approvedActivityPermitsTable_pageInfo" style="font-size: 0.8rem; color: var(--text-muted);">Page 1 of 1</span>
        <button type="button" class="btn-sm btn-outline" id="approvedActivityPermitsTable_nextBtn">Next <i class="fas fa-chevron-right"></i></button>
    </div>
    </div></div>



        <!-- TAB 3: Approved Permits -->
<div id="approved_permits" class="tab-content">
    <div class="table-card">
        <div style="padding: 20px 20px 0 20px;">
            <h3 class="section-title"><i class="fas fa-check-circle"></i> Approved Activity Permits</h3>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 14px;">
                <i class="fas fa-pen"></i> Click on any editable field to modify | <i class="fas fa-search"></i> Use search to filter records
            </p>
            
            <div class="table-header">
                <div style="font-size: 0.75rem; color: var(--text-muted);">
                    <i class="fas fa-database"></i> Showing <?php echo count($approved_permits); ?> approved permits
                </div>
                <input type="text" id="permitSearch" class="search-bar" placeholder="🔍 Search by Permit ID, Organization, Activity, Venue...">
            </div>

            <div class="permit-filter-chips">
                <button class="chip active" data-filter="all">All</button>
                <button class="chip" data-filter="on">In-Campus</button>
                <button class="chip" data-filter="off">Off-Campus</button>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="data-table" id="approvedPermitsTable">
                <thead>
                    <tr>
                        <th>Permit ID</th>
                        <th>Organization</th>
                        <th>Activity Title</th>
                        <th>Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Venue</th>
                        <th>Approval Date</th>
                        <th>Report Due</th>
                        <th>Actual Sub.</th>
                        <th>Rating %</th>
                        <th>AP +/-</th>
                        <th>AR +/-</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($approved_permits)): ?>
                        <?php foreach ($approved_permits as $permit): ?>
                        <tr class="permit-row" data-id="<?php echo htmlspecialchars($permit['permit_id'] ?? $permit['id']); ?>" data-type="<?php echo strtolower(htmlspecialchars($permit['type'] ?? $permit['campus_type'] ?? '')); ?>">
                            <td><?php echo htmlspecialchars($permit['permit_id'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($permit['organization'] ?? '-'); ?></td>
                            <td contenteditable="true" class="editable"><?php echo htmlspecialchars($permit['activity_title'] ?? '-'); ?></td>
                            <td contenteditable="true" class="editable"><?php echo htmlspecialchars($permit['type'] ?? $permit['campus_type'] ?? '-'); ?></td>
                            <td contenteditable="true" class="editable"><?php echo htmlspecialchars($permit['start_date'] ?? '-'); ?></td>
                            <td contenteditable="true" class="editable"><?php echo htmlspecialchars($permit['end_date'] ?? '-'); ?></td>
                            <td contenteditable="true" class="editable"><?php echo htmlspecialchars($permit['start_time'] ?? '-'); ?></td>
                            <td contenteditable="true" class="editable"><?php echo htmlspecialchars($permit['end_time'] ?? '-'); ?></td>
                            <td contenteditable="true" class="editable"><?php echo htmlspecialchars($permit['venue'] ?? '-'); ?></td>
                            <td contenteditable="true" class="editable"><?php echo htmlspecialchars($permit['approval_date'] ?? '-'); ?></td>
                            <td contenteditable="true" class="editable"><?php echo htmlspecialchars($permit['report_due'] ?? '-'); ?></td>
                            <td contenteditable="true" class="editable"><?php echo htmlspecialchars($permit['actual_submission'] ?? '-'); ?></td>
                            <td contenteditable="true" class="editable"><?php echo htmlspecialchars($permit['rating'] ?? '-'); ?></td>
                            <td contenteditable="true" class="editable"><?php echo htmlspecialchars($permit['ap_points'] ?? '-'); ?></td>
                            <td contenteditable="true" class="editable"><?php echo htmlspecialchars($permit['ar_points'] ?? '-'); ?></td>
                            <td contenteditable="true" class="editable"><?php echo htmlspecialchars($permit['remarks'] ?? '-'); ?></td>
                            <td>
                                <button class="btn-sm btn-edit" onclick="confirmSaveEdit(this)">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="17" style="text-align: center; color: var(--text-muted); padding: 20px;">
                                No approved permits found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="log-pagination" style="display: flex; justify-content: center; align-items: center; gap: 12px; padding: 16px 20px;">
            <button type="button" class="btn-sm btn-outline" id="approvedPermitsTable_prevBtn"><i class="fas fa-chevron-left"></i> Back</button>
            <span id="approvedPermitsTable_pageInfo" style="font-size: 0.8rem; color: var(--text-muted);">Page 1 of 1</span>
            <button type="button" class="btn-sm btn-outline" id="approvedPermitsTable_nextBtn">Next <i class="fas fa-chevron-right"></i></button>
        </div>
    </div> <!-- End of .table-card -->

    <!-- Export Button Container placed OUTSIDE the table-card, aligned to the bottom-right -->
   <!-- Export Button Container placed OUTSIDE the table-card, aligned to the bottom-right -->
<div style="margin-top: 12px; display: flex; justify-content: flex-end; width: 100%;">
    <button 
        type="button" 
        id="exportBtn"
        style="
            display: inline-flex; 
            align-items: stretch; 
            border: none; 
            padding: 0; 
            cursor: pointer; 
            overflow: hidden;
            font-family: inherit;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(75, 219, 154, 0.35);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        "
        onmouseover="this.style.transform='translateY(-2px) scale(1.02)'; this.style.boxShadow='0 6px 16px rgba(75, 219, 154, 0.45)';"
        onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 4px 12px rgba(75, 219, 154, 0.35)';"
        onclick="showConfirm('Export the approved permits to Excel?', exportToExcel, {okClass: 'btn-approve', okText: 'Export'})"
    >
        <span style="
            background-color: #5c6470; 
            color: #ffffff; 
            font-weight: 600; 
            letter-spacing: 0.3px;
            padding: 12px 20px; 
            display: flex; 
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
        ">
            <i class="fas fa-file-excel" style="font-size: 0.95rem;"></i>
            Export
        </span>
        <span style="
            background: linear-gradient(135deg, #6ee7ac, #4bdb9a); 
            padding: 12px 18px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
        ">
            <i class="fas fa-download" style="color: #ffffff; font-size: 0.95rem;"></i>
        </span>
    </button>
</div>
</div> <!-- End of #approved_permits -->

   <!-- TAB 4: Evaluate Reports -->
<div id="evaluate_reports" class="tab-content">
    <div class="table-card">
        <div style="padding: 20px 20px 0 20px;">
            <h3 class="section-title"><i class="fas fa-chart-line"></i> Pending Accomplishment Reports</h3>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table" id="evaluateReportsTable">
                <thead>
                    <tr>
                        <th>Permit ID</th>
                        <th>Organization</th>
                        <th>Activity Title</th>
                        <th>Submitted Date</th>
                        <th>Rating (%)</th>
                        <th>Reports</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($completed_AR)): ?>
                        <?php foreach ($completed_AR as $eval): ?>
                        <?php $isCancelled = ($eval['permit_status'] ?? '') === 'cancelled'; ?>
                        <tr<?php echo $isCancelled ? ' style="background-color: #fdecea;"' : ''; ?>>
                            <td><strong><?php echo htmlspecialchars($eval['permit_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($eval['organization']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($eval['activity_title']); ?>
                                <?php if ($isCancelled): ?>
                                    <span class="status-badge status-rejected" style="margin-left: 6px;">
                                        <i class="fas fa-ban"></i> Cancelled
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($eval['submission_date']); ?></td>
                            
                            <!-- Rating Input wrapped inside form -->
                            <td>
                                <form method="POST" action="index.php?action=evaluate_report" id="eval_form_<?php echo htmlspecialchars($eval['permit_id']); ?>">
                                    <input type="hidden" name="report_id" value="<?php echo htmlspecialchars($eval['report_id']); ?>">
                                    <input type="hidden" name="permit_id" value="<?php echo htmlspecialchars($eval['permit_id']); ?>">
                                    <input type="hidden" name="activity_title" value="<?php echo htmlspecialchars($eval['activity_title']); ?>">
                                    <input 
                                        type="number" 
                                        name="rating" 
                                        value="<?php echo htmlspecialchars($eval['pending_rating'] ?? ''); ?>" 
                                        min="0" 
                                        max="100" 
                                        step="0.01" 
                                        placeholder="0-100" 
                                        required 
                                        style="width: 80px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;"
                                    >
                                </form>
                            </td>

                          <td>
    <?php if (!empty($eval['files']) || $isCancelled): ?>
        <button type="button" 
                class="btn-sm btn-view" 
                data-eval-id="<?php echo htmlspecialchars($eval['report_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                onclick="handleViewEvalClick(this)">
            <i class="fas fa-file-pdf"></i> View Files
        </button>
    <?php else: ?>
        <span class="text-muted">No files attached</span>
    <?php endif; ?>
</td>

                            <!-- Action Column -->
<td>
    <button type="button" class="btn-sm btn-approve" onclick="confirmApproval('<?php echo htmlspecialchars($eval['permit_id']); ?>')">
        <i class="fas fa-check"></i> Approve
    </button>
</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted, #6c757d); padding: 30px; font-weight: 500;">
                                <i class="fas fa-info-circle" style="margin-right: 8px;"></i> No pending accomplishment reports to evaluate.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="log-pagination" style="display: flex; justify-content: center; align-items: center; gap: 12px; padding: 16px 20px;">
            <button type="button" class="btn-sm btn-outline" id="evaluateReportsTable_prevBtn"><i class="fas fa-chevron-left"></i> Back</button>
            <span id="evaluateReportsTable_pageInfo" style="font-size: 0.8rem; color: var(--text-muted);">Page 1 of 1</span>
            <button type="button" class="btn-sm btn-outline" id="evaluateReportsTable_nextBtn">Next <i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</div>

<div id="Organization_log" class="tab-content">
    <div class="table-card">
        <div style="padding: 20px 20px 0 20px;">
            <h3 class="section-title"><i class="fas fa-server"></i> Organization Event Logs</h3>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 14px;">
                <i class="fas fa-info-circle"></i> Monitor system events, startup/shutdown, and database operations
            </p>
            
            <div class="log-filter">
                <input type="text" id="OrgLogSearch" class="search-bar" placeholder="🔍 Search organization events...">
                <button class="btn-sm btn-view" onclick="refreshSystemLogs()"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table" id="orgLogsTable">
                <thead>
                    <tr><th>Timestamp</th><th>Event</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($Org_logs)): ?>
                        <?php foreach ($Org_logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                            <td><?php echo htmlspecialchars($log['event']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $log['status'] === 'Success' ? 'status-approved' : ($log['status'] === 'Warning' ? 'status-pending' : 'status-info'); ?>">
                                    <?php echo htmlspecialchars($log['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center;">No log entries found. Check if the file exists.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="log-pagination" style="display: flex; justify-content: center; align-items: center; gap: 12px; padding: 16px 20px;">
            <button type="button" class="btn-sm btn-outline" id="orgLogsTable_prevBtn"><i class="fas fa-chevron-left"></i> Back</button>
            <span id="orgLogsTable_pageInfo" style="font-size: 0.8rem; color: var(--text-muted);">Page 1 of 1</span>
            <button type="button" class="btn-sm btn-outline" id="orgLogsTable_nextBtn">Next <i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</div>

       <!-- TAB 7: Staff Logs -->
<div id="system_logs" class="tab-content">
    <div class="table-card">
        <div style="padding: 20px 20px 0 20px;">
            <h3 class="section-title"><i class="fas fa-server"></i> Staff Logs</h3>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 14px;">
                <i class="fas fa-info-circle"></i> Monitor system events, startup/shutdown, and database operations
            </p>
            
            <div class="log-filter">
                <input type="text" id="staffLogSearch" class="search-bar" placeholder="🔍 Search staff events...">
                <button class="btn-sm btn-view" onclick="refreshSystemLogs()"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table" id="staffLogsTable">
                <thead>
                    <tr><th>Timestamp</th><th>Event</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($Staff_logs)): ?>
                        <?php foreach ($Staff_logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                            <td><?php echo htmlspecialchars($log['event']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $log['status'] === 'Success' ? 'status-approved' : ($log['status'] === 'Warning' ? 'status-pending' : 'status-info'); ?>">
                                    <?php echo htmlspecialchars($log['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center;">No log entries found. Check if the file exists.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="log-pagination" style="display: flex; justify-content: center; align-items: center; gap: 12px; padding: 16px 20px;">
            <button type="button" class="btn-sm btn-outline" id="staffLogsTable_prevBtn"><i class="fas fa-chevron-left"></i> Back</button>
            <span id="staffLogsTable_pageInfo" style="font-size: 0.8rem; color: var(--text-muted);">Page 1 of 1</span>
            <button type="button" class="btn-sm btn-outline" id="staffLogsTable_nextBtn">Next <i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-ban"></i> Reject Application</h3>
            <button class="close-modal" onclick="closeRejectModal()">&times;</button>
        </div>
        <div style="padding: 8px 0;">
            <p style="margin-bottom: 12px; color: var(--text-muted);">Please provide a reason for rejecting this application:</p>
            <textarea id="rejectRemarks" class="remarks-textarea" rows="4" placeholder="Enter remarks/reason for rejection..."></textarea>
            <div class="modal-buttons">
                <button class="btn-sm btn-outline" onclick="closeRejectModal()">Cancel</button>
                <button class="btn-sm btn-reject" id="confirmRejectBtn"><i class="fas fa-check"></i> Confirm Rejection</button>
            </div>
        </div>
    </div>
</div>
<!-- 2. Modal Markup -->
<div id="viewEvalModal" class="modal" style="display: none;">
    <div class="modal-content" style="text-align: left;">
        <div class="modal-header" style="text-align: left;">
            <h3 style="text-align: left; margin: 0;">
                <i class="fas fa-folder-open"></i> Evaluation Documents
            </h3>
            <button class="close-modal" onclick="closeViewEvalModal()">&times;</button>
        </div>
        <div class="modal-body" style="text-align: left;">
            <?php foreach ($completed_AR as $eval): ?>
                <?php $isCancelled = ($eval['permit_status'] ?? '') === 'cancelled'; ?>
                <div id="eval_files_<?php echo htmlspecialchars($eval['report_id']); ?>" class="eval-file-group" style="display: none;">
                    <?php if ($isCancelled): ?>
                        <div style="background: #fdecea; border-left: 4px solid var(--rejected, #c0392b); border-radius: 6px; padding: 10px 14px; margin-bottom: 16px;">
                            <div style="font-weight: bold; margin-bottom: 4px; color: var(--rejected, #c0392b);">
                                <i class="fas fa-ban"></i> Activity Cancelled
                            </div>
                            <div style="color: var(--text-dark, #333);">
                                <?php echo !empty($eval['cancellation_reason']) ? nl2br(htmlspecialchars($eval['cancellation_reason'])) : '<span style="font-style: italic; color: var(--text-muted, #888);">No reason provided.</span>'; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($eval['files'])): ?>
                        <?php 
                            $accomplishmentFiles = array_filter($eval['files'], function($f) {
                                return ($f['file_type'] === 'accomplishment_report');
                            });
                            $evaluationFiles = array_filter($eval['files'], function($f) {
                                return ($f['file_type'] !== 'accomplishment_report');
                            });
                        ?>

                        <!-- Accomplishment Report Section -->
                        <div style="font-weight: bold; margin-bottom: 6px; display: block !important;">Accomplishment Report:</div>
                        <div style="margin: 0 0 16px 20px !important; display: block !important;">
                            <?php if (!empty($accomplishmentFiles)): ?>
                                <?php foreach ($accomplishmentFiles as $file): ?>
                                    <?php $displayName = !empty($file['original_filename']) ? $file['original_filename'] : basename($file['file_path']); ?>
                                    <div style="display: list-item !important; list-style-type: disc !important; margin-left: 15px !important; padding: 2px 0 !important;">
                                        <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" rel="noopener noreferrer" class="file-link">
                                            <?php echo htmlspecialchars($displayName); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="display: list-item !important; list-style-type: disc !important; margin-left: 15px !important; padding: 2px 0 !important;">
                                    <span style="color: var(--text-muted, #888); font-style: italic;">No File Uploaded</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Evaluation Report Section -->
                        <div style="font-weight: bold; margin-bottom: 6px; display: block !important;">Others:</div>
                        <div style="margin: 0 0 10px 20px !important; display: block !important;">
                            <?php if (!empty($evaluationFiles)): ?>
                                <?php foreach ($evaluationFiles as $file): ?>
                                    <?php $displayName = !empty($file['original_filename']) ? $file['original_filename'] : basename($file['file_path']); ?>
                                    <div style="display: list-item !important; list-style-type: disc !important; margin-left: 15px !important; padding: 2px 0 !important;">
                                        <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" rel="noopener noreferrer" class="file-link">
                                            <?php echo htmlspecialchars($displayName); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="display: list-item !important; list-style-type: disc !important; margin-left: 15px !important; padding: 2px 0 !important;">
                                    <span style="color: var(--text-muted, #888); font-style: italic;">No File Uploaded</span>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php elseif (!$isCancelled): ?>
                        <div style="padding: 15px; text-align: center; color: var(--text-muted, #6c757d);">
                            <i class="fas fa-folder-open" style="font-size: 2rem; margin-bottom: 8px;"></i>
                            <p>No requirement files uploaded yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal for viewing files -->
<div id="fileModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-folder-open"></i> Organization Documents</h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <div id="modalBody" class=""></div>
    </div>
</div>
<!-- System Logs Notification Modal -->
<div id="systemLogNotifyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-bell"></i> New Notification</h3>
            <button class="close-modal" onclick="closeSystemLogNotifyModal()">&times;</button>
        </div>
        <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
            <ul id="systemLogNotifyList" style="list-style: none; padding: 0; margin: 0;"></ul>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-sm btn-outline" onclick="closeSystemLogNotifyModal()">
                <i class="fas fa-check"></i> Acknowledge & Clear
            </button>
        </div>
    </div>
</div>

<!-- Custom Confirm Modal (replaces native browser confirm() dialogs) -->
<div id="customConfirmModal" class="modal">
    <div class="modal-content confirm-modal-content">
        <div class="confirm-icon"><i class="fas fa-question-circle"></i></div>
        <p class="confirm-message" id="confirmMessage"></p>
        <div class="confirm-actions">
            <button type="button" class="btn-sm btn-outline" id="confirmCancelBtn">Cancel</button>
            <button type="button" class="btn-sm btn-approve" id="confirmOkBtn">Yes, Continue</button>
        </div>
    </div>
</div>

<canvas id="confettiCanvas"></canvas>

<script src="js/enhancements.js"></script>
<script>
    // Global context tracking
// ---------------------------------------------------------------------
// showToast(), showConfirm(), and handleFormConfirm() are provided
// globally by js/enhancements.js — no need to redefine them here.

function checkPasswordStrength() {
    const passwordEl = document.getElementById('new_password');
    const strengthMsg = document.getElementById('strengthMessage');
    if (!passwordEl || !strengthMsg) return;
    const password = passwordEl.value;

    if (password.length === 0) {
        strengthMsg.innerHTML = '';
        return;
    }

    const missing = [];
    if (password.length < 8) missing.push('8+ characters');
    if (!/[A-Z]/.test(password)) missing.push('an uppercase letter');
    if (!/[a-z]/.test(password)) missing.push('a lowercase letter');
    if (!/[0-9]/.test(password)) missing.push('a number');
    if (!/[^A-Za-z0-9]/.test(password)) missing.push('a special character');

    if (missing.length === 0) {
        strengthMsg.innerHTML = '🟢 Strong — meets all requirements';
        strengthMsg.className = 'password-strength strength-strong';
    } else {
        strengthMsg.innerHTML = '🔴 Missing: ' + missing.join(', ');
        strengthMsg.className = 'password-strength strength-weak';
    }
}

function checkPasswordMatch() {
    const passwordEl = document.getElementById('new_password');
    const confirmEl = document.getElementById('confirm_password');
    const matchMsg = document.getElementById('matchMessage');
    if (!passwordEl || !confirmEl || !matchMsg) return;

    if (confirmEl.value.length === 0) {
        matchMsg.innerHTML = '';
        return;
    }

    if (passwordEl.value === confirmEl.value) {
        matchMsg.innerHTML = '✅ Passwords match';
        matchMsg.className = 'password-strength strength-strong';
    } else {
        matchMsg.innerHTML = '❌ Passwords do not match';
        matchMsg.className = 'password-strength strength-weak';
    }
}

function validateStaffPasswordForm(form) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    const isStrong = newPassword.length >= 8
        && /[A-Z]/.test(newPassword)
        && /[a-z]/.test(newPassword)
        && /[0-9]/.test(newPassword)
        && /[^A-Za-z0-9]/.test(newPassword);

    if (!isStrong) {
        showToast('New password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character.', 'error');
        return false;
    }

    if (newPassword !== confirmPassword) {
        showToast('New password and confirmation do not match.', 'error');
        return false;
    }

    showConfirm('Change your password?', function () {
        form.submit();
    }, {okClass: 'btn-approve', okText: 'Change Password'});
    return false;
}

function restrictToLetters(input) {
    input.value = input.value.replace(/[^A-Za-z\s'-]/g, '');
}

function formatContactNumber(input) {
    let digits = input.value.replace(/\D/g, '');

    // The prefix is always "09" — force it no matter what the user types
    // or deletes. Anything they type is treated as the digits that follow it.
    if (!digits.startsWith('09')) {
        digits = '09' + digits.replace(/^0+|^9+/, '');
    }
    digits = digits.slice(0, 11);

    let formatted = digits;
    if (digits.length > 7) {
        formatted = digits.slice(0, 4) + '-' + digits.slice(4, 7) + '-' + digits.slice(7);
    } else if (digits.length > 4) {
        formatted = digits.slice(0, 4) + '-' + digits.slice(4);
    }
    input.value = formatted;
}

function validateStaffProfileForm(form) {
    const letterPattern = /^[A-Za-z\s'-]*$/;
    const requiredLetterPattern = /^[A-Za-z\s'-]+$/;

    const firstName = document.getElementById('profile_first_name');
    const middleName = document.getElementById('profile_middle_name');
    const lastName = document.getElementById('profile_last_name');
    const contactNo = document.getElementById('profile_contact_no');

    if (!requiredLetterPattern.test(firstName.value) || !requiredLetterPattern.test(lastName.value) || !letterPattern.test(middleName.value)) {
        showToast('Names may only contain letters, spaces, hyphens, and apostrophes.', 'error');
        return false;
    }

    if (!/^09\d{2}-\d{3}-\d{4}$/.test(contactNo.value)) {
        showToast('Contact number must be in the format 0911-111-1111.', 'error');
        return false;
    }

    showConfirm('Save changes to your profile?', function () {
        form.submit();
    }, {okClass: 'btn-approve', okText: 'Save'});
    return false;
}

let currentRejectContext = null;       // Stores either 'org' or 'permit'
let currentRejectId = null;            // Stores the target ID
let currentCampusType = null;          // Stores campus type (for permits)
let currentRejectOrgName = null;       // Stores org name (for org rejection)
let currentRejectActivityTitle = null; // Stores activity title (for permit rejection)
function confirmApproval(permitId) {
    const remarks = prompt("Enter approval remarks (optional):");
    
    // If user clicks Cancel on the prompt, abort submission
    if (remarks === null) return; 

    const form = document.getElementById('eval_form_' + permitId);

    // Create dynamic input for remarks
    let remarksInput = form.querySelector('input[name="remarks"]');
    if (!remarksInput) {
        remarksInput = document.createElement('input');
        remarksInput.type = 'hidden';
        remarksInput.name = 'remarks';
        form.appendChild(remarksInput);
    }
    remarksInput.value = remarks;

    // Create dynamic submit trigger so $_POST['approve_report'] is set
    let actionInput = form.querySelector('input[name="evaluate_report"]');
    if (!actionInput) {
        actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'approve_report';
        actionInput.value = '1';
        form.appendChild(actionInput);
    }

    if (typeof fireConfetti === 'function') {
        fireConfetti(event.target);
    }
    setTimeout(function () { form.submit(); }, 450);
}

function handleViewEvalClick(button) {
    const evalId = button.getAttribute('data-eval-id');
    openViewEvalModal(evalId);
}
/**
 * Open Modal for Organization Application Rejection
 * @param {string|number} orgAccId - Account ID of the organization
 * @param {string} orgName - Name of the organization
 */
// Pass staff logs to JS
const staffSystemLogs = <?php echo json_encode($Other_log ?? []); ?>;

document.addEventListener('DOMContentLoaded', function() {
    if (staffSystemLogs && staffSystemLogs.length > 0) {
        showSystemLogNotifyModal(staffSystemLogs);
    }

    // Restore whichever tab was active before a page refresh, so actions
    // like the System Logs "Refresh" button don't bounce the user back
    // to the default Dashboard tab.
    try {
        var savedTab = sessionStorage.getItem('activeStaffTab');
        if (savedTab && document.getElementById(savedTab)) {
            switchTab(savedTab);
        }
    } catch (e) {
        // sessionStorage unavailable (e.g. private browsing) — safe to ignore
    }

    // Confirm before approving, then confetti burst, with a brief delay so
    // the animation is visible before the form actually submits.
    document.querySelectorAll('.approve-permit-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            window.showConfirm('Approve this activity permit?', function () {
                if (typeof fireConfetti === 'function') {
                    fireConfetti(form.querySelector('button[type="submit"]'));
                }
                setTimeout(function () { form.submit(); }, 450);
            }, {okClass: 'btn-approve', okText: 'Yes, Approve'});
        });
    });
});

function showSystemLogNotifyModal(logs) {
    const list = document.getElementById('systemLogNotifyList');
    list.innerHTML = '';

    logs.forEach(log => {
        const li = document.createElement('li');
        li.style.cssText = 'padding: 10px; border-bottom: 1px solid #eee;';

        const statusClass = log.status === 'Success' ? 'status-approved'
                           : log.status === 'Warning' ? 'status-pending'
                           : 'status-info';

        li.innerHTML = `
            <div style="font-size: 0.75rem; color: var(--text-muted);">${escapeHtml(log.timestamp)}</div>
            <div>${escapeHtml(log.event)}</div>
            <span class="status-badge ${statusClass}" style="margin-top: 4px; display: inline-block;">${escapeHtml(log.status)}</span>
        `;
        list.appendChild(li);
    });

    document.getElementById('systemLogNotifyModal').classList.add('active');
}function openViewEvalModal(evalId) {
    const modal = document.getElementById('viewEvalModal');
    
    // Hide all file groups
    document.querySelectorAll('.eval-file-group').forEach(group => {
        group.style.display = 'none';
    });
    
    // Show specific group
    const targetGroup = document.getElementById('eval_files_' + evalId);
    if (targetGroup) {
        targetGroup.style.display = 'block';
    }
    
    modal.style.display = 'flex';
}

function closeViewEvalModal() {
    document.getElementById('viewEvalModal').style.display = 'none';
}

function closeSystemLogNotifyModal() {
    document.getElementById('systemLogNotifyModal').classList.remove('active');

    // Clear the log file on the server after acknowledging
    fetch('index.php?action=clear_notif_logs', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Also clear the visible table immediately, no need to reload
            const tbody = document.querySelector('#staffLogsTable tbody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align: center;">No log entries found. Check if the file exists.</td></tr>';
            }
            if (typeof refreshStaffLogsTable === 'function') {
                refreshStaffLogsTable();
            }
        } else {
            console.error('Failed to clear logs:', data.message);
        }
    })
    .catch(error => console.error('Error clearing logs:', error));
}
function showOrgRejectModal(orgAccId, orgName) {
    currentRejectContext = 'org';
    currentRejectId = orgAccId;
    currentCampusType = null;
    currentRejectOrgName = orgName;
    currentRejectActivityTitle = null;

    document.getElementById('rejectRemarks').value = '';
    document.getElementById('rejectModal').classList.add('active');
}

/**
 * Open Modal for Permit Requirements Rejection
 * @param {string|number} permitDbId - Database ID of the permit
 * @param {string} campusType - Campus classification ('on' or 'off')
 * @param {string} activityTitle - Title of the activity
 */
function showPermitRejectModal(permitDbId, campusType, activityTitle) {
    currentRejectContext = 'permit';
    currentRejectId = permitDbId;
    currentCampusType = campusType;
    currentRejectActivityTitle = activityTitle;
    currentRejectOrgName = null;

    document.getElementById('rejectRemarks').value = '';
    document.getElementById('rejectModal').classList.add('active');
}
function showApprovePermitRejectModal(permitDbId, campusType, activityTitle) {
    currentRejectContext = 'approve_permit';
    currentRejectId = permitDbId;
    currentCampusType = campusType;
    currentRejectActivityTitle = activityTitle;
    currentRejectOrgName = null;

    document.getElementById('rejectRemarks').value = '';
    document.getElementById('rejectModal').classList.add('active');
}

/**
 * Close Modal and reset global variables
 */
function closeRejectModal() {
    const modal = document.getElementById('rejectModal');
    if (modal) {
        modal.classList.remove('active');
    }
    currentRejectContext = null;
    currentRejectId = null;
    currentCampusType = null;
    currentRejectOrgName = null;
    currentRejectActivityTitle = null;
}

// Confirmation Button Listener (the single, active listener for the reject modal)
document.addEventListener('DOMContentLoaded', function() {
    const confirmBtn = document.getElementById('confirmRejectBtn');
    
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            const remarks = document.getElementById('rejectRemarks').value.trim();
            
            if (!remarks) {
                showToast('Please provide a reason for rejection.', 'error');
                return;
            }

            // 1. REJECT PERMIT REQUIREMENTS
            if (currentRejectContext === 'permit' && currentRejectId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=reject_requirements';
                form.style.display = 'none';

                const fields = {
                    'permit_id': currentRejectId,
                    'campus_type': currentCampusType || 'on',
                    'activity_title': currentRejectActivityTitle,
                    'rejection_reason': remarks
                };

                for (const [key, value] of Object.entries(fields)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
                return;
            }

            // 2. REJECT ORGANIZATION APPLICATION
            if (currentRejectContext === 'org' && currentRejectId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const fields = {
                    'org_action': '1',
                    'org_id': currentRejectId,
                    'org_name': currentRejectOrgName,
                    'action_type': 'reject',
                    'remarks': remarks
                };

                for (const [key, value] of Object.entries(fields)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
                return;
            }

            showToast('An unexpected error occurred. Please try again.', 'error');
        });
    }
});

    /* ---------- Sidebar (mobile drawer) ---------- */
    function toggleSidebar() {
        document.getElementById('verticalTabs').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('open');
    }

    function closeSidebar() {
        document.getElementById('verticalTabs').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('open');
    }

    /* ---------- Tabs ---------- */
    function switchTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        
        // Remove active class from all vertical tabs
        document.querySelectorAll('.vertical-tab').forEach(btn => btn.classList.remove('active'));
        
        // Show selected tab content
        const selectedTab = document.getElementById(tabName);
        if (selectedTab) {
            selectedTab.classList.add('active');
        }
        
        // Find and activate the clicked button using onclick attribute
        const buttons = document.querySelectorAll('.vertical-tab');
        for (let btn of buttons) {
            const onclickAttr = btn.getAttribute('onclick');
            if (onclickAttr && onclickAttr.includes(`'${tabName}'`)) {
                btn.classList.add('active');
                break;
            }
        }

        // Auto-close the drawer on mobile after choosing a tab
        closeSidebar();

        // Scroll main content back to top on small screens
        if (window.innerWidth <= 900) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Remember which tab was active so a page refresh (e.g. the
        // System Logs "Refresh" button) can restore it instead of
        // always landing back on the default Dashboard tab.
        try {
            sessionStorage.setItem('activeStaffTab', tabName);
        } catch (e) {
            // sessionStorage unavailable (e.g. private browsing) — safe to ignore
        }
    }

// Encode PHP array into JavaScript object
const natureData = <?php echo json_encode($nature_breakdown ?? []); ?>;

function openNatureBreakdownModal() {
    const modal = document.getElementById('fileModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    
    // Set Title
    modalTitle.innerHTML = '<i class=""></i> Approved Permits';
    
    let contentHtml = '<ul class="file-list" style="list-style: none; padding: 0; margin: 0;">';
    
    if (natureData && natureData.length > 0) {
        natureData.forEach(item => {
            contentHtml += `
                <li style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee;">
                    <span style="font-weight: 600; color: #333;"> ${escapeHtml(item.nature_of_activity)}</span>
                    <div>
                        <span class="file-link" style="background: #e9f2ff; color: #007bff; padding: 3px 8px; border-radius: 4px; font-size: 0.85rem; margin-right: 4px;">
                            On-Campus: ${item.oncampus_count}
                        </span>
                        <span class="file-link" style="background: #e6f4ea; color: #1e7e34; padding: 3px 8px; border-radius: 4px; font-size: 0.85rem; margin-right: 4px;">
                            Off-Campus: ${item.offcampus_count}
                        </span>
                        <span class="file-link" style="background: #f8f9fa; color: #212529; font-weight: bold; padding: 3px 8px; border-radius: 4px; font-size: 0.85rem;">
                            Total: ${item.total_count}
                        </span>
                    </div>
                </li>
            `;
        });
    } else {
        contentHtml += '<li style="padding: 15px; text-align: center; color: #777;">No permit records found.</li>';
    }
    
    contentHtml += '</ul>';
    
    // Footer link to switch tabs
    contentHtml += `
        <div style="margin-top: 15px; text-align: right;">
            <a href="javascript:void(0)" onclick="switchTab('approved_permits'); closeModal();" class="file-link" style="font-weight: bold;">
                View All Permits &rarr;
            </a>
        </div>
    `;
    
    modalBody.innerHTML = contentHtml;
    modal.classList.add('active');
}

// Utility function to close the modal
function closeModal() {
    const modal = document.getElementById('fileModal');
    if (modal) modal.classList.remove('active');
}

// XSS Prevention Helper
function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
   function viewOrgFiles(orgId, orgType) {
        const modal = document.getElementById('fileModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        
        modalTitle.innerHTML = '<i class="fas fa-folder-open"></i> Organization Documents - ' + escapeHtml(orgId);
        
        // Find the selected org data object from the table
        const pendingOrgs = <?php echo json_encode($pending_orgs ?? []); ?>;
        const approvedOrgs = <?php echo json_encode($approved_orgs ?? []); ?>;
        const orgData = [...pendingOrgs, ...approvedOrgs].find(o => o.id === orgId) || {};
        const files = orgData.files || {
            LOA: orgData.LOA,
            AAF: orgData.AAF,
            POBIC: orgData.POBIC,
            TAP: orgData.TAP,
            RCAB: orgData.RCAB
        };

        const renderFileLink = (label, fileName) => {
            if (fileName && fileName.trim() !== '') {
                const filePath = 'uploads/org_documents/' + encodeURIComponent(fileName);
                return `<li><strong>${label}:</strong> <a href="${filePath}" target="_blank" rel="noopener noreferrer" class="file-link">${escapeHtml(fileName)}</a></li>`;
            }
            return `<li> <strong>${label}:</strong> <span style="color: var(--text-muted, #888); font-style: italic;">No File Uploaded</span></li>`;
        };

        let filesHtml = '<ul class="file-list" style="list-style: none; padding: 0;">';
        filesHtml += renderFileLink('Letter of Application', files.LOA);
        filesHtml += renderFileLink('Accomplished Application Form', files.AAF);
        filesHtml += renderFileLink('Photocopy of BSU ID Cards', files.POBIC);
        filesHtml += renderFileLink('Tentative Action Plan', files.TAP);
        filesHtml += renderFileLink('Constitution & By-Laws', files.RCAB);
        
        if (orgType === 'Probationary') {
            if (orgData.org_description) {
                filesHtml += `<li> <strong>Organization Description:</strong> <span class="file-link">${escapeHtml(orgData.org_description)}</span></li>`;
            }
            if (orgData.institution_description) {
                filesHtml += `<li> <strong>Institution Description:</strong> <span class="file-link">${escapeHtml(orgData.institution_description)}</span></li>`;
            }
        }
        filesHtml += '</ul>';
        
        modalBody.innerHTML = filesHtml;
        modal.classList.add('active');
    }

 async function viewPermitFiles(permitId, campusType = 'on', displayId = null) {
        const modal = document.getElementById('fileModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');

        modalTitle.innerHTML = `<i class="fas fa-file-alt"></i> Permit Documents - ${escapeHtml(displayId || permitId)}`;
        modalBody.innerHTML = `<div style="padding: 20px; text-align: center;"><i class="fas fa-spinner fa-spin"></i> Loading documents...</div>`;
        modal.classList.add('active');

        try {
            const response = await fetch(`index.php?action=get_permit_files&permit_id=${permitId}&campus_type=${campusType}`);
            const data = await response.json();

            if (!data.success || !data.files || data.files.length === 0) {
                modalBody.innerHTML = `
                    <div style="padding: 15px; text-align: center; color: var(--text-muted, #6c757d);">
                        <i class="fas fa-folder-open" style="font-size: 2rem; margin-bottom: 8px;"></i>
                        <p>No requirement files uploaded for this permit yet.</p>
                    </div>`;
                return;
            }

            const proposalFile = data.files.find(f => (f.doc_type || '').toLowerCase() === 'proposal');
            const otherFiles = data.files.filter(f => (f.doc_type || '').toLowerCase() !== 'proposal');

            // Force block flow layout to break any parent flex/space-between styles
            let filesHtml = '<div style="display: block !important; text-align: left !important; width: 100% !important;">';

            // Proposal Section
            filesHtml += `<div style="font-weight: bold; margin-bottom: 6px; display: block !important;">Proposal:</div>`;
            filesHtml += '<div style="margin: 0 0 16px 20px !important; display: block !important;">';
            if (proposalFile) {
                const filePath = `uploads/permit_requirements/${encodeURIComponent(campusType)}/${encodeURIComponent(proposalFile.stored_filename)}`;
                const displayName = proposalFile.original_filename || proposalFile.stored_filename;
                filesHtml += `
                    <div style="display: list-item !important; list-style-type: disc !important; margin-left: 15px !important; padding: 2px 0 !important; width: auto !important; float: none !important;">
                        <a href="${filePath}" target="_blank" rel="noopener noreferrer" class="file-link" style="display: inline !important;">${escapeHtml(displayName)}</a>
                    </div>`;
            } else {
                filesHtml += `
                    <div style="display: list-item !important; list-style-type: disc !important; margin-left: 15px !important; padding: 2px 0 !important; width: auto !important; float: none !important;">
                        <span style="color: var(--text-muted, #888); font-style: italic;">No File Uploaded</span>
                    </div>`;
            }
            filesHtml += '</div>';

            // Others Section
            if (otherFiles.length > 0) {
                filesHtml += `<div style="font-weight: bold; margin-bottom: 6px; display: block !important;">Others:</div>`;
                filesHtml += '<div style="margin: 0 0 10px 20px !important; display: block !important;">';
                otherFiles.forEach(file => {
                    const filePath = `uploads/permit_requirements/${encodeURIComponent(campusType)}/${encodeURIComponent(file.stored_filename)}`;
                    const displayName = file.original_filename || file.stored_filename;
                    filesHtml += `
                        <div style="display: list-item !important; list-style-type: disc !important; margin-left: 15px !important; padding: 2px 0 !important; width: auto !important; float: none !important;">
                            <a href="${filePath}" target="_blank" rel="noopener noreferrer" class="file-link" style="display: inline !important;">${escapeHtml(displayName)}</a>
                        </div>`;
                });
                filesHtml += '</div>';
            }

            filesHtml += '</div>';

            modalBody.innerHTML = filesHtml;
        } catch (error) {
            console.error('Error fetching permit files:', error);
            modalBody.innerHTML = `
                <div style="padding: 15px; color: #c5221f;">
                    <i class="fas fa-exclamation-circle"></i> Failed to load files. Please try again.
                </div>`;
        }
    }
    function autoResizeInput(input) {
    // Create a hidden span to measure the actual pixel width of the input's text
    const span = document.createElement('span');
    span.style.visibility = 'hidden';
    span.style.position = 'absolute';
    span.style.whiteSpace = 'pre';
    span.style.font = window.getComputedStyle(input).font;
    span.textContent = input.value || input.placeholder || '';
    document.body.appendChild(span);
    input.style.width = (span.offsetWidth + 20) + 'px'; // +20px padding buffer
    document.body.removeChild(span);
}

function initAutoResizeInputs() {
    document.querySelectorAll('.table-input').forEach(input => {
        autoResizeInput(input); // Set initial width on page load
        input.addEventListener('input', () => autoResizeInput(input)); // Resize as user types
    });
}

document.addEventListener('DOMContentLoaded', initAutoResizeInputs);
    function viewApprovedOrgFiles(orgId, status) {
        const modal = document.getElementById('fileModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        
        modalTitle.innerHTML = '<i class="fas fa-folder-open"></i> Documents - ' + orgId + ' (' + status.toUpperCase() + ')';
        
        let filesHtml = '<ul class="file-list">';
        filesHtml += '<li>📄 Letter of Application: <a href="#" target="_blank" rel="noopener noreferrer" class="file-link">letter.pdf</a></li>';
        filesHtml += '<li>📄 Application Form: <a href="#" target="_blank" rel="noopener noreferrer" class="file-link">form.pdf</a></li>';
        filesHtml += '<li>📄 BSU ID Cards: <a href="#" target="_blank" rel="noopener noreferrer" class="file-link">ids.pdf</a></li>';
        filesHtml += '<li>📄 Action Plan: <a href="#" target="_blank" rel="noopener noreferrer" class="file-link">action_plan.pdf</a></li>';
        filesHtml += '<li>📄 Members List: <a href="#" target="_blank" rel="noopener noreferrer" class="file-link">members.pdf</a></li>';
        filesHtml += '<li>📄 Constitution & By-Laws: <a href="#" target="_blank" rel="noopener noreferrer" class="file-link">constitution.pdf</a></li>';
        
        if (status === 'Probationary') {
            filesHtml += '<li>📄 Organization Description: <a href="#" target="_blank" rel="noopener noreferrer" class="file-link">description.pdf</a></li>';
            filesHtml += '<li>📄 SEC Registration: <a href="#" target="_blank" rel="noopener noreferrer" class="file-link">sec_reg.pdf</a></li>';
        }
        filesHtml += '</ul>';
        
        if (status === 'Probationary') {
            filesHtml += '<div style="margin-top: 12px; padding: 10px; background: #d1ecf1; border-radius: 14px; font-size: 0.7rem;"><i class="fas fa-info-circle"></i> <strong>NEW RSO Status</strong> - Probationary period.</div>';
        } else if (status === 'partial') {
            filesHtml += '<div style="margin-top: 12px; padding: 10px; background: #fed7aa; border-radius: 14px; font-size: 0.7rem;"><i class="fas fa-exclamation-triangle"></i> <strong>PARTIAL RSO Status</strong> - Limited privileges.</div>';
        } else {
            filesHtml += '<div style="margin-top: 12px; padding: 10px; background: #d1fae5; border-radius: 14px; font-size: 0.7rem;"><i class="fas fa-check-circle"></i> <strong>FULL RSO Status</strong> - All privileges granted.</div>';
        }
        
        modalBody.innerHTML = filesHtml;
        modal.classList.add('active');
    }
    
    function closeModal() {
        document.getElementById('fileModal').classList.remove('active');
    }

   function confirmSaveEdit(button) {
    showConfirm('Save changes to this permit record?', function () {
        saveEdit(button);
    }, {okClass: 'btn-approve', okText: 'Save'});
}

   function saveEdit(button) {
    const row = button.closest('tr');
    const permitId = row.getAttribute('data-id');
    const cells = row.querySelectorAll('td');

    // Extract updated values using column indexes matching table headers
    const updatedData = {
        permit_id: permitId,
        activity_title: cells[2].innerText.trim(),
        type: cells[3].innerText.trim(),
        start_date: cells[4].innerText.trim(),
        end_date: cells[5].innerText.trim(),
        start_time: cells[6].innerText.trim(),
        end_time: cells[7].innerText.trim(),
        venue: cells[8].innerText.trim(),
        approval_date: cells[9].innerText.trim(),
        report_due: cells[10].innerText.trim(),
        actual_submission: cells[11].innerText.trim(),
        rating: cells[12].innerText.trim(),
        ap_points: cells[13].innerText.trim(),
        ar_points: cells[14].innerText.trim(),
        remarks: cells[15].innerText.trim()
    };

    // UI Feedback: Disable button during fetch request
    button.disabled = true;
    const originalBtnHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    fetch('update_permit', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify(updatedData)
})
.then(async response => {
    const text = await response.text();
    try {
        return JSON.parse(text);
    } catch (err) {
        // Output the raw PHP error directly into the alert modal
        throw new Error("PHP Outputted non-JSON:\n\n" + text.replace(/<[^>]*>?/gm, ''));
    }
})
.then(data => {
    if (data.success) {
        button.innerHTML = '<i class="fas fa-check"></i> Saved';
        button.style.backgroundColor = '#28a745';
        setTimeout(() => {
            button.innerHTML = originalBtnHTML;
            button.style.backgroundColor = '';
            button.disabled = false;
        }, 2000);
    } else {
        showToast('Error updating permit: ' + (data.message || 'Unknown error.'), 'error');
        button.innerHTML = originalBtnHTML;
        button.disabled = false;
    }
})
.catch(error => {
    console.error('Save failed:', error);
    showToast(error.message, 'error');
    button.innerHTML = originalBtnHTML;
    button.disabled = false;
});
}

    function approveEvaluation(permitId) {
        showToast('Accomplishment report for ' + permitId + ' has been APPROVED!', 'success');
    }

    function rejectEvaluation(permitId) {
        showToast('Accomplishment report for ' + permitId + ' has been DISAPPROVED.', 'error');
    }

    function downloadReport() {
        showToast('Downloading report as PDF...', 'info');
    }
    
    function setupApprovedPermitsPagination(rowsPerPage = 15) {
        const table = document.getElementById('approvedPermitsTable');
        if (!table) return;
        const searchInput = document.getElementById('permitSearch');
        const chipsContainer = document.querySelector('.permit-filter-chips');
        let currentPage = 1;
        let activeFilter = 'all';

        function getFilteredRows() {
            const filter = searchInput ? searchInput.value.toLowerCase() : '';
            const allRows = Array.from(table.querySelectorAll('tbody tr.permit-row'));
            return allRows.filter(row => {
                const rowType = (row.getAttribute('data-type') || '').toLowerCase();
                if (activeFilter !== 'all' && rowType !== activeFilter) return false;
                if (!filter) return true;
                const cells = Array.from(row.querySelectorAll('td'));
                return cells.slice(0, -1).some(cell => (cell.textContent || '').toLowerCase().indexOf(filter) > -1);
            });
        }

        function render() {
            const allRows = Array.from(table.querySelectorAll('tbody tr'));
            const filtered = getFilteredRows();
            const totalPages = Math.max(1, Math.ceil(filtered.length / rowsPerPage));
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            allRows.forEach(row => { row.style.display = 'none'; });
            filtered
                .slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage)
                .forEach(row => { row.style.display = ''; });

            const pageInfo = document.getElementById('approvedPermitsTable_pageInfo');
            if (pageInfo) pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
            const prevBtn = document.getElementById('approvedPermitsTable_prevBtn');
            const nextBtn = document.getElementById('approvedPermitsTable_nextBtn');
            if (prevBtn) prevBtn.disabled = currentPage <= 1;
            if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
        }

        const prevBtn = document.getElementById('approvedPermitsTable_prevBtn');
        const nextBtn = document.getElementById('approvedPermitsTable_nextBtn');
        if (prevBtn) prevBtn.addEventListener('click', () => { currentPage--; render(); });
        if (nextBtn) nextBtn.addEventListener('click', () => { currentPage++; render(); });
        if (searchInput) searchInput.addEventListener('input', () => { currentPage = 1; render(); });

        if (chipsContainer) {
            const chips = chipsContainer.querySelectorAll('.chip');
            chips.forEach(chip => {
                chip.addEventListener('click', () => {
                    chips.forEach(c => c.classList.remove('active'));
                    chip.classList.add('active');
                    activeFilter = (chip.getAttribute('data-filter') || 'all').toLowerCase();
                    currentPage = 1;
                    render();
                });
            });
        }

        render();
        return render;
    }

    function filterAuditLogs() {
        const input = document.getElementById('auditSearch');
        if (!input) return;
        const filter = input.value.toLowerCase();
        const rows = document.querySelectorAll('#auditLogsTable tbody tr');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.indexOf(filter) > -1 ? '' : 'none';
        });
    }

    function setupTablePagination(tableId, searchInputId, rowsPerPage = 20) {
        const table = document.getElementById(tableId);
        if (!table) return null;
        let currentPage = 1;

        function getFilteredRows() {
            const searchInput = searchInputId ? document.getElementById(searchInputId) : null;
            const filter = searchInput ? searchInput.value.toLowerCase() : '';
            const allRows = Array.from(table.querySelectorAll('tbody tr'));
            if (!filter) return allRows;
            return allRows.filter(row => row.innerText.toLowerCase().indexOf(filter) > -1);
        }

        function render() {
            const allRows = Array.from(table.querySelectorAll('tbody tr'));
            const filtered = getFilteredRows();
            const totalPages = Math.max(1, Math.ceil(filtered.length / rowsPerPage));
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            allRows.forEach(row => { row.style.display = 'none'; });
            filtered
                .slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage)
                .forEach(row => { row.style.display = ''; });

            const pageInfo = document.getElementById(tableId + '_pageInfo');
            if (pageInfo) pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;

            const prevBtn = document.getElementById(tableId + '_prevBtn');
            const nextBtn = document.getElementById(tableId + '_nextBtn');
            if (prevBtn) prevBtn.disabled = currentPage <= 1;
            if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
        }

        const prevBtn = document.getElementById(tableId + '_prevBtn');
        const nextBtn = document.getElementById(tableId + '_nextBtn');
        if (prevBtn) prevBtn.addEventListener('click', () => { currentPage--; render(); });
        if (nextBtn) nextBtn.addEventListener('click', () => { currentPage++; render(); });

        if (searchInputId) {
            const searchInput = document.getElementById(searchInputId);
            if (searchInput) {
                searchInput.addEventListener('input', () => { currentPage = 1; render(); });
            }
        }

        render();
        return render;
    }

    let refreshOrgLogsTable, refreshStaffLogsTable;
    document.addEventListener('DOMContentLoaded', function () {
        refreshOrgLogsTable = setupTablePagination('orgLogsTable', 'OrgLogSearch', 20);
        refreshStaffLogsTable = setupTablePagination('staffLogsTable', 'staffLogSearch', 20);

        // Organizations — 5 per page
        setupTablePagination('pendingOrgsTable', null, 5);
        setupTablePagination('approvedOrgsTable', null, 5);

        // Staff Accounts — 10 per page
        setupTablePagination('staffAccountsTable', null, 10);

        // Pending / Approved Activity Permits — 15 per page each, kept separate
        setupTablePagination('pendingPermitsTable', null, 15);
        setupTablePagination('approvedActivityPermitsTable', null, 15);

        // Accomplishment Reports (Evaluate Reports) — 15 per page
        setupTablePagination('evaluateReportsTable', null, 15);

        // Approved Permits (editable table, with search + campus-type chips) — 15 per page
        setupApprovedPermitsPagination(15);
    });

    function exportAuditLogs() {
        let csvContent = "Timestamp,User,Action Performed,IP Address\n";
        document.querySelectorAll('#auditLogsTable tbody tr').forEach(row => {
            const cells = row.querySelectorAll('td');
            const rowData = Array.from(cells).map(cell => '"' + cell.innerText.replace(/"/g, '""') + '"').join(',');
            csvContent += rowData + '\n';
        });
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'audit_logs_' + new Date().toISOString().slice(0,19) + '.csv';
        a.click();
        URL.revokeObjectURL(url);
    }

    function refreshSystemLogs() {
        location.reload();
    }
    
    function handleLogout() {
    showConfirm('Are you sure you want to logout?', function () {
        window.location.href = 'logout';   // hits UsersController::logout()
    }, {okClass: 'btn-reject', okText: 'Logout'});
}

    // Close mobile drawer automatically if the viewport is resized to desktop width
    window.addEventListener('resize', function() {
        if (window.innerWidth > 900) {
            closeSidebar();
        }
    });
    /**
 * 1. Export Approved Permits to Excel (.xlsx)
 */
function exportToExcel() {
    const table = document.getElementById('approvedPermitsTable');
    if (!table) { showToast('Table not found.', 'error'); return; }

    // 1. Clone table to clean up hidden action buttons/inputs before export
    const tableClone = table.cloneNode(true);

    // 2. Remove the "ACTION" column (last header and last cell in every row)
    const rows = tableClone.querySelectorAll('tr');
    rows.forEach(row => {
        if (row.cells.length > 0) {
            row.deleteCell(-1); // Delete last cell (Action column)
        }
    });

    // 3. Generate SheetJS Workbook and download
    const wb = XLSX.utils.table_to_book(tableClone, { sheet: "Approved Permits" });
    const fileName = `Approved_Permits_${new Date().toISOString().slice(0, 10)}.xlsx`;
    XLSX.writeFile(wb, fileName);

    // // 4. Prompt user to clean up records from database and disk after export completes
    // setTimeout(() => {
    //     if (confirm("Export complete! Do you want to permanently clear all approved permits and their uploaded files now?")) {
    //         clearPermitsAndResetSequence();
    //     }
    // }, 500); // Slight delay so file download finishes initializing
}



/**
 * 2. Export Approved Permits to Landscape Printable PDF
 */
function exportToPDF() {
    const table = document.getElementById('approvedPermitsTable');
    if (!table) { showToast('Table not found.', 'error'); return; }

    // Clone table to adjust styling and strip action buttons for PDF layout
    const container = document.createElement('div');
    container.style.padding = '15px';
    container.style.fontFamily = 'Arial, sans-serif';

    // Header Title for the PDF Document
    const header = document.createElement('div');
    header.innerHTML = `
        <h3 style="text-align: center; color: #1e3d2f; margin-bottom: 5px;">BENGUET STATE UNIVERSITY</h3>
        <h4 style="text-align: center; margin-top: 0; margin-bottom: 15px;">Student Organizations & Activities Unit (SOAU) - Approved Permits</h4>
        <p style="font-size: 11px; color: #555; text-align: right;">Generated on: ${new Date().toLocaleDateString()}</p>
    `;
    container.appendChild(header);

    const tableClone = table.cloneNode(true);
    
    // Remove the last column (ACTION) from clone
    const rows = tableClone.querySelectorAll('tr');
    rows.forEach(row => {
        if (row.cells.length > 0) {
            row.deleteCell(-1);
        }
    });

    // Apply compact styling for PDF output
    tableClone.style.width = '100%';
    tableClone.style.borderCollapse = 'collapse';
    tableClone.style.fontSize = '9px';
    
    const allCells = tableClone.querySelectorAll('th, td');
    allCells.forEach(cell => {
        cell.style.border = '1px solid #ccc';
        cell.style.padding = '4px 6px';
        cell.style.textAlign = 'left';
    });

    container.appendChild(tableClone);

    // Configure html2pdf settings for landscape layout
    const opt = {
        margin:       [0.3, 0.3, 0.3, 0.3],
        filename:     `Approved_Permits_${new Date().toISOString().slice(0, 10)}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'in', format: 'legal', orientation: 'landscape' }
    };

    // Render and download PDF
    html2pdf().set(opt).from(container).save();
}

function clearPermitsAndResetSequence() {
    showConfirm(
        'Are you sure you want to clear all approved permits and reset the sequence counter back to 0001? This action cannot be undone.',
        function () {
            fetch('index.php?action=clear_and_reset_permits', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                let rawText = await response.text();

                // Extract JSON string safely to prevent PHP warning pollution
                const jsonStart = rawText.indexOf('{');
                const jsonEnd = rawText.lastIndexOf('}');

                if (jsonStart !== -1 && jsonEnd !== -1) {
                    rawText = rawText.substring(jsonStart, jsonEnd + 1);
                }

                try {
                    return JSON.parse(rawText);
                } catch (e) {
                    console.error('Raw server response:', rawText);
                    throw new Error('Server returned invalid JSON. Check console output.');
                }
            })
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Operation failed:', error);
                showToast(error.message, 'error');
            });
        },
        { okClass: 'btn-reject', okText: 'Clear & Reset' }
    );
}
</script>

</body>
<!-- SheetJS for Excel Export -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.min.js"></script>

<!-- html2pdf.js for Printable PDF Export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</html>