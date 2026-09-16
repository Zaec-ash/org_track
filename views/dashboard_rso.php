<?php

// Statistics
$total_applications = count($applications);
$pending_applications = count(array_filter($applications, fn($app) => $app['status'] === 'pending'));
$approved_applications = count(array_filter($applications, fn($app) => $app['status'] === 'approved'));
$rejected_applications = count(array_filter($applications, fn($app) => $app['status'] === 'rejected'));
$pending_reports = count(array_filter($completed_activities, fn($act) => $act['report_status'] === 'pending'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Dashboard | BSU ORG-Track</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="design/dash_staff.css" rel="stylesheet">
    <link href="design/dash_rso.css" rel="stylesheet">
    <link href="design/enhancements.css" rel="stylesheet">
   
</head>
<body>
    <!-- Toast container -->
<div id="toastContainer" class="toast-container"></div>
 <?php include "views/partials/nav_dash.php"; ?>
<!-- Mobile Overlay & Toggle -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="dashboard-layout">

    <!-- Sidebar Navigation -->
    <div class="vertical-tabs" id="verticalTabs">
        <button class="vertical-tab active" onclick="switchTab('dashboard')">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </button>
        <button class="vertical-tab" onclick="switchTab('applications')">
            <i class="fas fa-paper-plane"></i> Activity Permit Application
        </button>
        <button class="vertical-tab" onclick="switchTab('approved_permits')">
            <i class="fas fa-folder-open"></i> Approved Permits
        </button>
        <button class="vertical-tab" onclick="switchTab('accomplishment')">
            <i class="fas fa-file-alt"></i> Accomplishment Reports
        </button>
        <button class="vertical-tab" onclick="switchTab('activities')">
            <i class="fas fa-chart-line"></i> Activity Viewing
        </button>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- TAB: Dashboard Overview -->
        <div id="dashboard" class="tab-content active-tab">
            <div class="welcome-header">
                <div class="welcome-text">
                    <h2><i class="fas fa-clipboard-list"></i> Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
                    <p>Organization Dashboard - Manage activity permits, submit accomplishment reports, and track your activities</p>
                    <span class="role-badge"><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($org_name); ?></span>
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
        <div onclick="switchTab('applications')" style="cursor: pointer; text-align: center; flex: 1;">
            <div class="stat-value" data-target="<?php echo (int)$total_applications; ?>">0</div>
            <div class="stat-label"></i> Total Applications</div>
        </div>

        <div onclick="switchTab('applications')" style="cursor: pointer; text-align: center; flex: 1;">
            <div class="stat-value" data-target="<?php echo (int)$pending_applications; ?>">0</div>
            <div class="stat-label"></i> Pending Review</div>
        </div>

  <div onclick="switchTab('applications')" style="cursor: pointer; text-align: center; flex: 1;">
    <div class="stat-value" data-target="<?php echo (int)$approved_applications; ?>">0</div>
    <div class="stat-label"><i class=""></i> Approved Permit</div>
</div>

        <div onclick="switchTab('accomplishment')" style="cursor: pointer; text-align: center; flex: 1;">
            <div class="stat-value" data-target="<?php echo (int)$pending_reports; ?>">0</div>
            <div class="stat-label"></i> Pending Reports</div>
        </div>
        </div>
        </div>
        </div>


        <!-- TAB: Organization Profile -->
        <div id="profile" class="tab-content">
    <div class="info-card">
        <h3 class="section-title">
            <i class="fas fa-building"></i> Organization Profile
        </h3>

        <?php if ($profile_update_message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $profile_update_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="acc_id" value="<?php echo htmlspecialchars($user_id); ?>">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Organization Name</span>
                    <input type="text" name="org_name" class="profile-input" value="<?php echo htmlspecialchars($org_name); ?>" required>
                </div>
                <div class="info-item">
                    <span class="info-label">Email Address</span>
                    <input type="email" name="email" class="profile-input" value="<?php echo htmlspecialchars($user_email); ?>" required>
                </div>
                <div class="info-item">
                    <span class="info-label">Representative First Name</span>
                    <input type="text" name="first_name" class="profile-input" value="<?php echo htmlspecialchars($first_name); ?>" required>
                </div>
                <div class="info-item">
                    <span class="info-label">Representative Last Name</span>
                    <input type="text" name="last_name" class="profile-input" value="<?php echo htmlspecialchars($last_name); ?>" required>
                </div>
                <div class="info-item">
                    <span class="info-label">Contact Number</span>
                    <input type="text" name="contact_no" class="profile-input" value="<?php echo htmlspecialchars($contact_no); ?>" required>
                </div>
                <div class="info-item">
                    <span class="info-label">Organization Type</span>
                    <span class="info-value"><i class="fas fa-trophy"></i> Recognized Student Organization (RSO)</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="info-value"><span class="status-badge status-<?php echo htmlspecialchars($org_status); ?>"></i> <?php echo htmlspecialchars($org_status); ?></span></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date Registered</span>
                    <span class="info-value">January 15, <?php echo $current_year - 1; ?></span>
                </div>
            </div>
            <div style="margin-top: 24px; text-align: right;">
                <button type="submit" name="update_profile" class="btn-primary"><i class="fas fa-save"></i> Update Profile</button>
            </div>
        </form>
    </div>
</div>

        <!-- TAB: Security / Password Change -->
        <div id="password" class="tab-content">
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

                <form method="POST" class="password-form">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Current Password</label>
                        <input type="password" name="current_password" placeholder="Enter your current password" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> New Password</label>
                        <input type="password" name="new_password" id="new_password" placeholder="Enter new password (min. 8 characters)" required onkeyup="checkPasswordStrength()">
                        <div class="password-strength" id="strengthMessage"></div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-check-circle"></i> Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your new password" required onkeyup="checkPasswordMatch()">
                        <div class="password-strength" id="matchMessage"></div>
                    </div>
                    <button type="submit" name="change_password" class="btn-primary"><i class="fas fa-save"></i> Update Password</button>
                </form>
            </div>
        </div>

        <!-- TAB: Activity Permit Applications -->
        <div id="applications" class="tab-content">
            <div class="info-card">
                <h3 class="section-title">
                    <i class="fas fa-paper-plane"></i> Activity Permit Applications
                </h3>
                
                <?php if ($permit_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $permit_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (count($pending_requirement_apps) > 0): ?>
                    <div class="table-card" style="margin-bottom: 0;">
                        <table class="data-table" id="pendingRequirementsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Activity Title</th>
                                    <th>Date Requested</th>
                                    <th>Campus Type</th>
                                    <th>Status</th>
                                    <th>Requirements</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_requirement_apps as $app): ?>
                                    <tr class="paginated-row">
                                        <td><strong><a href="update_permit_registration?id=<?php echo $app['db_id']; ?>&type=<?php echo $app['campus_type_raw']; ?>" class="view-link"><?php echo $app['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($app['title']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($app['date_requested'])); ?></td>
                                        <td><?php echo $app['campus_type']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $app['status']; ?>">
                                                <?php echo ucfirst($app['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                           <?php 
                                            $reqStatus = $app['requirements_status'] ?? 'pending'; 
                                            $isSubmitted = ($reqStatus === 'submitted');
                                        ?>

                                        <span class="requirements-badge requirements-<?php echo $isSubmitted ? 'submitted' : 'not-submitted'; ?>">
                                            <?php if ($isSubmitted): ?>
                                                <i class="fas fa-check"></i> Submitted
                                            <?php else: ?>
                                                <i class="fas fa-exclamation-triangle"></i> Pending
                                            <?php endif; ?>
                                        </span>
                                        </td>
                                        <td>
                                                <?php if ($app['status'] === 'pending' && $app['requirements_status'] !== 'submitted'): ?>
                                                    <button class="btn-requirements" onclick="openRequirementsModal('<?php echo $app['db_id']; ?>', '<?php echo htmlspecialchars($app['title']); ?>', '<?php echo htmlspecialchars($app['type']); ?>', '<?php echo htmlspecialchars($app['campus_type_raw']); ?>')">
                                                        <i class="fas fa-upload"></i> Submit Reqs
                                                    </button>
                                                <?php endif; ?>
                                                
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-controls" id="pendingRequirementsTablePagination" style="display:flex; justify-content:space-between; align-items:center; margin-top:15px; flex-wrap:wrap; gap:10px;">
                        <div class="pagination-info" id="pendingRequirementsTablePaginationInfo" style="color:var(--text-muted); font-size:0.85rem;"></div>
                        <div class="pagination-buttons" style="display:flex; gap:8px; align-items:center;">
                            <button type="button" class="btn-sm btn-outline" id="pendingRequirementsTablePrevBtn" onclick="changeTablePage('pendingRequirementsTable', -1)">
                                <i class="fas fa-chevron-left"></i> Back
                            </button>
                            <span id="pendingRequirementsTablePageIndicator" style="font-size:0.85rem; color:var(--text-muted);"></span>
                            <button type="button" class="btn-sm btn-outline" id="pendingRequirementsTableNextBtn" onclick="changeTablePage('pendingRequirementsTable', 1)">
                                Next <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <p>No pending applications found</p>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 28px; text-align: center;">
                    <button class="btn-primary" onclick="newApplication()">
                        <i class="fas fa-plus"></i> New Application 
                    </button>
                </div>
            </div>
        </div>

        <!-- TAB: Approved Permits -->
        <div id="approved_permits" class="tab-content">
            <div class="info-card">
                <h3 class="section-title">
                    <i class="fas fa-folder-open"></i> Approved Permits
                </h3>

                <?php if (count($approved_submitted_permits) > 0): ?>
                    <div class="table-card" style="margin-bottom: 0;">
                        <table class="data-table" id="approvedPermitsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Activity Title</th>
                                    <th>Date Requested</th>
                                    <th>Campus Type</th>
                                    <th>Status</th>
                                    <th>Requirements</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($approved_submitted_permits as $app): ?>
                                    <tr class="paginated-row">
                                        <td><strong><?php echo htmlspecialchars($app['id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($app['title']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($app['date_requested'])); ?></td>
                                        <td><?php echo $app['campus_type']; ?></td>
                                        <td>
                                            <?php if ($app['status'] === 'cancelled'): ?>
                                                <span class="status-badge status-rejected">
                                                    <i class="fas fa-ban"></i> Cancelled
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-approved">
                                                    <i class="fas fa-check"></i> Approved
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="requirements-badge requirements-submitted">
                                                <i class="fas fa-check"></i> Submitted
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-sm btn-view" onclick="viewMyPermitFiles('<?php echo $app['db_id']; ?>', '<?php echo $app['campus_type_raw']; ?>', '<?php echo htmlspecialchars($app['id']); ?>')">
                                                <i class="fas fa-folder-open"></i> View Files
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-controls" id="approvedPermitsTablePagination" style="display:flex; justify-content:space-between; align-items:center; margin-top:15px; flex-wrap:wrap; gap:10px;">
                        <div class="pagination-info" id="approvedPermitsTablePaginationInfo" style="color:var(--text-muted); font-size:0.85rem;"></div>
                        <div class="pagination-buttons" style="display:flex; gap:8px; align-items:center;">
                            <button type="button" class="btn-sm btn-outline" id="approvedPermitsTablePrevBtn" onclick="changeTablePage('approvedPermitsTable', -1)">
                                <i class="fas fa-chevron-left"></i> Back
                            </button>
                            <span id="approvedPermitsTablePageIndicator" style="font-size:0.85rem; color:var(--text-muted);"></span>
                            <button type="button" class="btn-sm btn-outline" id="approvedPermitsTableNextBtn" onclick="changeTablePage('approvedPermitsTable', 1)">
                                Next <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📁</div>
                        <p>No approved permits with submitted requirements yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB: Accomplishment Reports -->
        <div id="accomplishment" class="tab-content">
            <?php if ($accomplishment_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $accomplishment_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="info-card">
                <h3 class="section-title">
                    <i class="fas fa-clock"></i> Pending Accomplishment Reports
                </h3>
                
                <?php
                $pending_reports_list = array_filter($completed_activities, fn($act) => $act['report_status'] === 'pending');
                if (count($pending_reports_list) > 0):
                ?>
                    <div style="overflow-x: auto;">
                    <table class="data-table" id="pendingAccomplishmentTable">
                        <thead>
                            <tr>
                                <th>Permit ID</th>
                                <th>Activity Title</th>
                                <th>Type</th>
                                <th>Start Date</th>
                                <th>Venue</th>
                                <th>Report Due</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_reports_list as $activity): ?>
                                <tr class="paginated-row">
                                    <td><strong><?php echo $activity['permit_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($activity['activity_title']); ?></td>
                                    <td><?php echo $activity['type']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($activity['start_date'])); ?></td>
                                    <td><?php echo $activity['venue']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($activity['report_due'])); ?></td>
                                    <td>
                                        <span class="requirements-badge requirements-not-submitted">
                                            <i class="fas fa-hourglass-half"></i> Not Submitted
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px; flex-wrap: nowrap;">
                                            <button class="btn-sm btn-update" style="white-space: nowrap;" onclick="openAccomplishmentModal('<?php echo $activity['permit_id']; ?>', '<?php echo htmlspecialchars($activity['activity_title']); ?>')">
                                                <i class="fas fa-upload"></i> Submit Report
                                            </button>
                                            <button class="btn-sm btn-reject" style="white-space: nowrap;" onclick="openAccomplishmentModal('<?php echo $activity['permit_id']; ?>', '<?php echo htmlspecialchars($activity['activity_title']); ?>', true)">
                                                <i class="fas fa-ban"></i> Activity Cancelled
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <div class="pagination-controls" id="pendingAccomplishmentTablePagination" style="display:flex; justify-content:space-between; align-items:center; margin-top:15px; flex-wrap:wrap; gap:10px;">
                        <div class="pagination-info" id="pendingAccomplishmentTablePaginationInfo" style="color:var(--text-muted); font-size:0.85rem;"></div>
                        <div class="pagination-buttons" style="display:flex; gap:8px; align-items:center;">
                            <button type="button" class="btn-sm btn-outline" id="pendingAccomplishmentTablePrevBtn" onclick="changeTablePage('pendingAccomplishmentTable', -1)">
                                <i class="fas fa-chevron-left"></i> Back
                            </button>
                            <span id="pendingAccomplishmentTablePageIndicator" style="font-size:0.85rem; color:var(--text-muted);"></span>
                            <button type="button" class="btn-sm btn-outline" id="pendingAccomplishmentTableNextBtn" onclick="changeTablePage('pendingAccomplishmentTable', 1)">
                                Next <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <p>No pending accomplishment reports</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="info-card">
                <h3 class="section-title">
                    <i class="fas fa-history"></i> Submitted Reports History
                </h3>
                
                <?php if (count($submitted_reports) > 0): ?>
                    <table class="data-table" id="submittedReportsTable">
                        <thead>
                            <tr>
                                <th>Permit ID</th>
                                <th>Activity Title</th>
                                <th>Submission Date</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submitted_reports as $report): ?>
                                <?php $isCancelled = ($report['permit_status'] ?? '') === 'cancelled'; ?>
                                <tr class="paginated-row">
                                    <td><strong><?php echo $report['permit_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($report['activity_title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($report['submission_date'])); ?></td>
                                    <td>
                                        <?php if ($isCancelled): ?>
                                            <span style="color: var(--text-muted);">&mdash;</span>
                                        <?php else: ?>
                                            <div class="rating-ring" data-rating="<?php echo (float)$report['rating']; ?>"></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isCancelled): ?>
                                            <span class="status-badge status-rejected">
                                                <i class="fas fa-ban"></i> Cancelled
                                            </span>
                                        <?php else: ?>
                                            <span class="requirements-badge requirements-submitted">
                                                <i class="fas fa-check-circle"></i> <?php echo ucfirst($report['status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                    <button type="button" class="btn-sm btn-view" onclick="openViewReportModal(<?php echo $report['report_id']; ?>, '<?php echo $report['permit_id']; ?>')">
    <i class="fas fa-file-pdf"></i> View Report
</button>
                                        
                                        
                                </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination-controls" id="submittedReportsTablePagination" style="display:flex; justify-content:space-between; align-items:center; margin-top:15px; flex-wrap:wrap; gap:10px;">
                        <div class="pagination-info" id="submittedReportsTablePaginationInfo" style="color:var(--text-muted); font-size:0.85rem;"></div>
                        <div class="pagination-buttons" style="display:flex; gap:8px; align-items:center;">
                            <button type="button" class="btn-sm btn-outline" id="submittedReportsTablePrevBtn" onclick="changeTablePage('submittedReportsTable', -1)">
                                <i class="fas fa-chevron-left"></i> Back
                            </button>
                            <span id="submittedReportsTablePageIndicator" style="font-size:0.85rem; color:var(--text-muted);"></span>
                            <button type="button" class="btn-sm btn-outline" id="submittedReportsTableNextBtn" onclick="changeTablePage('submittedReportsTable', 1)">
                                Next <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <p>No submitted reports yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB: Activity Viewing -->
        <div id="activities" class="tab-content">
            <div class="info-card">
                <h3 class="section-title">
                    <i class="fas fa-chart-line"></i> Organization Activity List
                </h3>
                
                <div class="table-header">
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        <i class="fas fa-database"></i> Showing all approved activities and permits
                    </div>
                    <input type="text" id="activitySearch" class="search-bar" placeholder="🔍 Search activities..." onkeyup="filterActivities()">
                </div>

                <div class="filter-chips">
                    <button class="chip active" data-filter="all">All</button>
                    <button class="chip" data-filter="on">In-Campus</button>
                    <button class="chip" data-filter="off">Off-Campus</button>
                </div>

                <div class="universal-table-wrapper">
                    <table class="universal-table" id="activityTable">
                        <thead>
                            <tr>
                                <th>Permit ID</th>
                                <th class="col-org">Organization</th>
                                <th class="col-title">Activity Title</th>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($approved_permits)): ?>
                        <?php foreach ($approved_permits as $permit): ?>
                        <tr class="permit-row" data-id="<?php echo htmlspecialchars($permit['permit_id'] ?? $permit['id']); ?>" data-type="<?php echo strtolower(htmlspecialchars($permit['type'] ?? $permit['campus_type'] ?? '')); ?>">
                            <td><?php echo htmlspecialchars($permit['permit_id'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($permit['organization'] ?? '-'); ?></td>
                            <td ><?php echo htmlspecialchars($permit['activity_title'] ?? '-'); ?></td>
                            <td ><?php echo htmlspecialchars($permit['type'] ?? $permit['campus_type'] ?? '-'); ?></td>
                            <td ><?php echo htmlspecialchars($permit['start_date'] ?? '-'); ?></td>
                            <td ><?php echo htmlspecialchars($permit['end_date'] ?? '-'); ?></td>
                            <td ><?php echo htmlspecialchars($permit['start_time'] ?? '-'); ?></td>
                            <td ><?php echo htmlspecialchars($permit['end_time'] ?? '-'); ?></td>
                            <td ><?php echo htmlspecialchars($permit['venue'] ?? '-'); ?></td>
                            <td ><?php echo htmlspecialchars($permit['approval_date'] ?? '-'); ?></td>
                            <td ><?php echo htmlspecialchars($permit['report_due'] ?? '-'); ?></td>
                            <td ><?php echo htmlspecialchars($permit['actual_submission'] ?? '-'); ?></td>
                            <td ><?php echo htmlspecialchars($permit['rating'] ?? '-'); ?></td>
                            <td ><?php echo htmlspecialchars($permit['ap_points'] ?? '-'); ?></td>
                            <td ><?php echo htmlspecialchars($permit['ar_points'] ?? '-'); ?></td>
                            <td ><?php echo htmlspecialchars($permit['remarks'] ?? '-'); ?></td>
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

                <!-- Pagination Controls -->
                <div class="pagination-controls" id="activityPagination" style="display:flex; justify-content:space-between; align-items:center; margin-top:15px; flex-wrap:wrap; gap:10px;">
                    <div class="pagination-info" id="activityPaginationInfo" style="color:var(--text-muted); font-size:0.85rem;"></div>
                    <div class="pagination-buttons" style="display:flex; gap:8px; align-items:center;">
                        <button type="button" class="btn-sm btn-outline" id="activityPrevBtn" onclick="changeActivityPage(-1)">
                            <i class="fas fa-chevron-left"></i> Back
                        </button>
                        <span id="activityPageIndicator" style="font-size:0.85rem; color:var(--text-muted);"></span>
                        <button type="button" class="btn-sm btn-outline" id="activityNextBtn" onclick="changeActivityPage(1)">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

<!-- Modal: Requirements Upload -->
<div id="requirementsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-upload"></i> Submit Activity Permit Requirements</h3>
            <button class="close-modal" onclick="closeRequirementsModal()">&times;</button>
        </div>
       <form method="POST" action="submit_requirements" enctype="multipart/form-data" id="requirementsForm">
    <input type="hidden" name="application_id" id="modal_application_id">
    <input type="hidden" name="campus_type" id="modal_campus_type">
    <input type="hidden" name="activity_title" id="modal_activity_title_input">
    <div class="modal-body">
        <p style="margin-bottom: 15px; color: var(--text-muted);">
            <strong>Application ID: <span id="modal_app_id_display"></span></strong><br>
            Activity: <strong><span id="modal_activity_title"></span></strong><br>
            Activity Type: <strong><span id="modal_activity_type"></span></strong>
        </p>
                
                <ul class="file-upload-list" id="requirementsFileList">
                    <li>
                        <span class="file-label"><i class="fas fa-file-alt"></i> Activity Design</span>
                        <input type="file" name="proposal" class="file-input" accept=".pdf,.doc,.docx" required>
                    </li>
                    <li>
                        <span class="file-label"><i class="fas fa-chart-line"></i> Risk Assessment</span>
                        <input type="file" name="budget" class="file-input" accept=".pdf,.doc,.docx" required>
                    </li>
                    <li>
                        <span class="file-label"><i class="fas fa-shield-alt"></i> Others</span>
                        <input type="file" name="safety_plan" class="file-input" accept=".pdf" required>
                    </li>
                </ul>
            </div>  
            <div class="modal-footer">
                <button type="button" class="btn-requirements" style="background: #95a5a6;" onclick="closeRequirementsModal()">Cancel</button>
                <button type="submit" name="submit_requirements" class="btn-requirements" style="background: var(--bsu-green);">Submit Requirements</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: View Report Files -->
<!-- Modal: View Report Files -->
<div id="viewReportModal" class="modal">
    <div class="modal-content" style="text-align: left;">
        <div class="modal-header" style="text-align: left;">
            <h3 style="text-align: left; margin: 0;"><i class="fas fa-folder-open"></i> Submitted Report Documents</h3>
            <button class="close-modal" onclick="closeViewReportModal()">&times;</button>
        </div>
        <div class="modal-body" style="text-align: left;">
            <p style="margin-bottom: 15px; color: var(--text-muted); text-align: left;">
                <strong>Permit ID: <span id="view_modal_permit_id"></span></strong>
            </p>
            
            <?php foreach ($submitted_reports as $report): ?>
                <div id="report_files_<?php echo $report['report_id']; ?>" class="report-file-group" style="display: none; text-align: left; width: 100%;">
                    <?php if (!empty($report['files'])): ?>
                        <?php 
                            // Separate accomplishment report (proposal) and other files
                            $proposalFiles = array_filter($report['files'], function($f) {
                                return ($f['file_type'] === 'accomplishment_report');
                            });
                            $otherFiles = array_filter($report['files'], function($f) {
                                return ($f['file_type'] !== 'accomplishment_report');
                            });
                        ?>

                        <!-- Proposal / Accomplishment Report Section -->
                        <div style="font-weight: bold; margin-bottom: 6px; display: block !important;">Proposal:</div>
                        <div style="margin: 0 0 16px 20px !important; display: block !important;">
                            <?php if (!empty($proposalFiles)): ?>
                                <?php foreach ($proposalFiles as $file): ?>
                                    <?php 
                                        $displayName = !empty($file['original_filename']) ? $file['original_filename'] : basename($file['file_path']);
                                    ?>
                                    <div style="display: list-item !important; list-style-type: disc !important; margin-left: 15px !important; padding: 2px 0 !important; width: auto !important; float: none !important;">
                                        <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" rel="noopener noreferrer" class="file-link" style="display: inline !important;">
                                            <?php echo htmlspecialchars($displayName); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="display: list-item !important; list-style-type: disc !important; margin-left: 15px !important; padding: 2px 0 !important; width: auto !important; float: none !important;">
                                    <span style="color: var(--text-muted, #888); font-style: italic;">No File Uploaded</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Others Section -->
                        <?php if (!empty($otherFiles)): ?>
                            <div style="font-weight: bold; margin-bottom: 6px; display: block !important;">Others:</div>
                            <div style="margin: 0 0 10px 20px !important; display: block !important;">
                                <?php foreach ($otherFiles as $file): ?>
                                    <?php 
                                        $displayName = !empty($file['original_filename']) ? $file['original_filename'] : basename($file['file_path']);
                                    ?>
                                    <div style="display: list-item !important; list-style-type: disc !important; margin-left: 15px !important; padding: 2px 0 !important; width: auto !important; float: none !important;">
                                        <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" rel="noopener noreferrer" class="file-link" style="display: inline !important;">
                                            <?php echo htmlspecialchars($displayName); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div style="padding: 15px; text-align: center; color: var(--text-muted, #6c757d);">
                            <i class="fas fa-folder-open" style="font-size: 2rem; margin-bottom: 8px;"></i>
                            <p>No requirement files uploaded for this permit yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-footer" style="text-align: right;">
            <button type="button" class="btn-requirements" style="background: #95a5a6;" onclick="closeViewReportModal()">Close</button>
        </div>
    </div>
</div>
<!-- Modal: View Approved Permit Requirement Files -->
<div id="permitFilesModal" class="modal">
    <div class="modal-content" style="max-width: 600px; text-align: left;">
        <div class="modal-header" style="text-align: left;">
            <h3 id="permitFilesModalTitle" style="text-align: left; margin: 0;"><i class="fas fa-folder-open"></i> Permit Documents</h3>
            <button class="close-modal" onclick="closePermitFilesModal()">&times;</button>
        </div>
        <div id="permitFilesModalBody" class="modal-body" style="text-align: left;"></div>
        <div class="modal-footer" style="text-align: right;">
            <button type="button" class="btn-requirements" style="background: #95a5a6;" onclick="closePermitFilesModal()">Close</button>
        </div>
    </div>
</div>
<!-- Modal: Accomplishment Report -->
<div id="accomplishmentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-file-alt"></i> <span id="accomplishmentModalTitle">Submit Accomplishment Report</span></h3>
            <button class="close-modal" onclick="closeAccomplishmentModal()">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" id="accomplishmentForm">
            <input type="hidden" name="permit_id" id="modal_permit_id">
            <input type="hidden" name="activity_title" id="modal_activity_title_accomplishment">
            <input type="hidden" name="is_cancelled" id="modal_is_cancelled" value="0">
            <div class="modal-body">
                
                <!-- Side-by-Side Flex Container -->
                <div style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 20px;">
                    <!-- Left Column: Activity Details -->
                    <div style="flex: 1;">
                        <p style="margin: 0; color: var(--text-muted); line-height: 1.5;">
                            <strong>Permit ID: <span id="modal_permit_id_display"></span></strong><br>
                            Activity: <strong><span id="modal_activity_title_display"></span></strong>
                        </p>
                    </div>

                    <!-- Right Column: Rating Input (hidden when reporting a cancellation) -->
                    <div style="flex: 1;" id="ratingFieldWrapper">
                        <label for="modal_rating" style="display: block; font-weight: 600; margin-bottom: 5px;">
                            <i class="fas fa-star" style="color: #f39c12;"></i> Rating (%)
                        </label>
                        <input type="number" name="rating" id="modal_rating" class="profile-input" min="0" max="100" step="0.01" placeholder="e.g. 95" required style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                </div>

                <!-- Cancellation Reason (only shown when reporting a cancellation) -->
                <div id="cancelReasonWrapper" style="display: none; margin-bottom: 20px;">
                    <label for="modal_cancellation_reason" style="display: block; font-weight: 600; margin-bottom: 5px;">
                        <i class="fas fa-comment-dots" style="color: #e74c3c;"></i> Reason for Cancellation
                    </label>
                    <textarea name="cancellation_reason" id="modal_cancellation_reason" class="profile-input" rows="3" placeholder="Explain why this activity was cancelled..." style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; resize: vertical;"></textarea>
                </div>
                
                <ul class="file-upload-list">
                    <li>
                        <span class="file-label"><i class="fas fa-file-pdf"></i> <span id="accReportFileLabel">Accomplishment Report (.pdf)</span></span>
                        <input type="file" name="accomplishment_report" class="file-input" accept=".pdf" required>
                    </li>
                    <li>
    <span class="file-label"><i class="fas fa-folder-plus"></i> Others (Additional Documents)</span>
    <input type="file" name="others[]" class="file-input" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" multiple>
</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-requirements" style="background: #95a5a6;" onclick="closeAccomplishmentModal()">Cancel</button>
                <button type="submit" name="submit_accomplishment" class="btn-requirements" id="accomplishmentSubmitBtn" style="background: var(--bsu-green);">Submit Report</button>
            </div>
        </form>
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

<script src="js/enhancements.js"></script>
<script src="script.js"></script>

<script>
    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active-tab');
        });
        
        document.querySelectorAll('.vertical-tab').forEach(btn => {
            btn.classList.remove('active');
        });
        
        const selectedTab = document.getElementById(tabName);
        if (selectedTab) {
            selectedTab.classList.add('active-tab');
        }
        
        const buttons = document.querySelectorAll('.vertical-tab');
        for (let btn of buttons) {
            const onclickAttr = btn.getAttribute('onclick');
            if (onclickAttr && onclickAttr.includes(`'${tabName}'`)) {
                btn.classList.add('active');
                break;
            }
        }
        closeSidebar();
    }
    
    function toggleSidebar() {
        document.getElementById('verticalTabs').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('open');
    }

    function closeSidebar() {
        document.getElementById('verticalTabs').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('open');
    }

    // showConfirm() is provided globally by js/enhancements.js

    function handleLogout() {
        showConfirm('Are you sure you want to logout?', function () {
            window.location.href = 'logout';
        }, {okClass: 'btn-reject', okText: 'Logout'});
    }
    
    // ============================================================
    // Activity Viewing table: search + chip filter + pagination
    // (15 rows per page, with Back/Next controls)
    // ============================================================
    const ACTIVITY_PAGE_SIZE = 15;
    let activityCurrentPage = 1;

    function getActivityRows() {
        const table = document.getElementById('activityTable');
        if (!table) return [];
        return Array.from(table.querySelectorAll('tbody tr.permit-row'));
    }

    function getActiveChipFilter() {
        const activeChip = document.querySelector('#activities .chip.active');
        return activeChip ? activeChip.dataset.filter : 'all';
    }

    // Called by the search box's onkeyup — resets to page 1 and re-renders
    function filterActivities() {
        activityCurrentPage = 1;
        renderActivityPage();
    }

    function renderActivityPage() {
        const rows = getActivityRows();
        if (rows.length === 0) {
            const info = document.getElementById('activityPaginationInfo');
            const indicator = document.getElementById('activityPageIndicator');
            const prevBtn = document.getElementById('activityPrevBtn');
            const nextBtn = document.getElementById('activityNextBtn');
            if (info) info.textContent = '';
            if (indicator) indicator.textContent = '';
            if (prevBtn) prevBtn.disabled = true;
            if (nextBtn) nextBtn.disabled = true;
            return;
        }

        const searchInput = document.getElementById('activitySearch');
        const filterText = (searchInput?.value || '').toLowerCase();
        const chipFilter = getActiveChipFilter();

        const matched = rows.filter(row => {
            const matchesChip = chipFilter === 'all' || row.dataset.type === chipFilter;
            if (!matchesChip) return false;
            if (!filterText) return true;
            return row.textContent.toLowerCase().indexOf(filterText) > -1;
        });

        const totalItems = matched.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / ACTIVITY_PAGE_SIZE));
        if (activityCurrentPage > totalPages) activityCurrentPage = totalPages;
        if (activityCurrentPage < 1) activityCurrentPage = 1;

        // Hide every row first, then reveal only the current page's slice of matches
        rows.forEach(row => { row.style.display = 'none'; });

        const startIdx = (activityCurrentPage - 1) * ACTIVITY_PAGE_SIZE;
        const pageRows = matched.slice(startIdx, startIdx + ACTIVITY_PAGE_SIZE);
        pageRows.forEach(row => { row.style.display = ''; });

        const infoEl = document.getElementById('activityPaginationInfo');
        if (infoEl) {
            infoEl.textContent = totalItems === 0
                ? 'No matching activities found'
                : `Showing ${startIdx + 1}–${Math.min(startIdx + ACTIVITY_PAGE_SIZE, totalItems)} of ${totalItems}`;
        }

        const indicatorEl = document.getElementById('activityPageIndicator');
        if (indicatorEl) {
            indicatorEl.textContent = `Page ${activityCurrentPage} of ${totalPages}`;
        }

        const prevBtn = document.getElementById('activityPrevBtn');
        const nextBtn = document.getElementById('activityNextBtn');
        if (prevBtn) prevBtn.disabled = activityCurrentPage <= 1;
        if (nextBtn) nextBtn.disabled = activityCurrentPage >= totalPages;
    }

    function changeActivityPage(direction) {
        activityCurrentPage += direction;
        renderActivityPage();
    }

    // Watch the filter chips for their 'active' class changing (however that's
    // triggered — inline click here or an external handler in script.js) and
    // re-run pagination whenever the selected chip changes.
    document.querySelectorAll('#activities .chip').forEach(chip => {
        chip.addEventListener('click', function () {
            document.querySelectorAll('#activities .chip').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            activityCurrentPage = 1;
            renderActivityPage();
        });
    });

    // Initial render on page load
    renderActivityPage();
    
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
        a.download = 'org_audit_logs_' + new Date().toISOString().slice(0,10) + '.csv';
        a.click();
        URL.revokeObjectURL(url);
    }
    
    function viewApplication(appId) {
        showToast(`Viewing application #${appId} — in production, this would show full details.`, 'info');
    }
    
    function newApplication() {
        window.location.href = 'permit_registration';
    }
    
    function resolveRequirementsKey(appType, campusType) {
        // campusType must be 'on' or 'off' to match the DB's permit_type enum.
        // Fall back to 'on' if something unexpected comes through, rather than
        // silently failing to find any bucket at all.
        const campusBucket = REQUIREMENTS_BY_TYPE[campusType] ? campusType : 'on';

        const knownTypes = Object.keys(REQUIREMENTS_BY_TYPE[campusBucket]).filter(k => k !== 'default');
        const typeKey = knownTypes.includes(appType)
            ? appType
            : (knownTypes.includes('Others') ? 'Others' : 'default');

        return { campusBucket, typeKey };
    }

   function openRequirementsModal(appId, appTitle, appType, campusType) {
    document.getElementById('modal_application_id').value = appId;
    document.getElementById('modal_campus_type').value = campusType;
    document.getElementById('modal_app_id_display').innerText = appId;
    document.getElementById('modal_activity_title_input').value = appTitle;
    document.getElementById('modal_activity_title').innerText = appTitle;
    document.getElementById('modal_activity_type').innerText = appType;

        const { campusBucket, typeKey } = resolveRequirementsKey(appType, campusType);
        const requirements = REQUIREMENTS_BY_TYPE[campusBucket][typeKey]
            ?? REQUIREMENTS_BY_TYPE[campusBucket]['default'];

        renderRequirementsList(requirements);

        document.getElementById('requirementsModal').classList.add('active');
    }

    function renderRequirementsList(requirements) {
    const list = document.getElementById('requirementsFileList');
    list.innerHTML = ''; 

    requirements.forEach(req => {
        const isMultiple = req.multiple ? 'multiple' : '';
        const requiredAttr = req.multiple ? '' : 'required';
        
        const li = document.createElement('li');
        li.innerHTML = `
            <span class="file-label"><i class="fas fa-file-alt"></i> ${req.label}</span>
            <input type="file" name="${req.name}" class="file-input" accept="${req.accept}" ${isMultiple} ${requiredAttr}>
        `;
        list.appendChild(li);
    });
}

    function closeRequirementsModal() {
        document.getElementById('requirementsModal').classList.remove('active');
        document.getElementById('requirementsForm').reset();
    }
    
    function openAccomplishmentModal(permitId, activityTitle, isCancelled = false) {
        document.getElementById('modal_permit_id').value = permitId;
        document.getElementById('modal_activity_title_accomplishment').value = activityTitle;
        document.getElementById('modal_permit_id_display').innerText = permitId;
        document.getElementById('modal_activity_title_display').innerText = activityTitle;

        document.getElementById('modal_is_cancelled').value = isCancelled ? '1' : '0';

        const ratingWrapper = document.getElementById('ratingFieldWrapper');
        const ratingInput = document.getElementById('modal_rating');
        const title = document.getElementById('accomplishmentModalTitle');
        const submitBtn = document.getElementById('accomplishmentSubmitBtn');
        const fileLabel = document.getElementById('accReportFileLabel');
        const reasonWrapper = document.getElementById('cancelReasonWrapper');
        const reasonInput = document.getElementById('modal_cancellation_reason');

        if (isCancelled) {
            ratingWrapper.style.display = 'none';
            ratingInput.removeAttribute('required');
            ratingInput.value = '';
            reasonWrapper.style.display = '';
            reasonInput.setAttribute('required', 'required');
            title.innerText = 'Report Activity Cancellation';
            submitBtn.innerText = 'Submit Cancellation';
            submitBtn.style.background = '#e74c3c';
            fileLabel.innerText = 'Supporting Document (.pdf)';
        } else {
            ratingWrapper.style.display = '';
            ratingInput.setAttribute('required', 'required');
            reasonWrapper.style.display = 'none';
            reasonInput.removeAttribute('required');
            reasonInput.value = '';
            title.innerText = 'Submit Accomplishment Report';
            submitBtn.innerText = 'Submit Report';
            submitBtn.style.background = 'var(--bsu-green)';
            fileLabel.innerText = 'Accomplishment Report (.pdf)';
        }

        document.getElementById('accomplishmentModal').classList.add('active');
    }
    
    function closeAccomplishmentModal() {
        document.getElementById('accomplishmentModal').classList.remove('active');
        document.getElementById('accomplishmentForm').reset();
        document.getElementById('modal_is_cancelled').value = '0';
        document.getElementById('ratingFieldWrapper').style.display = '';
        document.getElementById('modal_rating').setAttribute('required', 'required');
        document.getElementById('cancelReasonWrapper').style.display = 'none';
        document.getElementById('modal_cancellation_reason').removeAttribute('required');
    }
    function openViewReportModal(reportId, permitId) {
    document.getElementById('view_modal_permit_id').innerText = permitId;

    // Hide all file groups first
    document.querySelectorAll('.report-file-group').forEach(el => el.style.display = 'none');

    // Show the target report files div
    const targetGroup = document.getElementById('report_files_' + reportId);
    if (targetGroup) {
        targetGroup.style.display = 'block';
    }

    document.getElementById('viewReportModal').classList.add('active');
}

function closeViewReportModal() {
    document.getElementById('viewReportModal').classList.remove('active');
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

async function viewMyPermitFiles(permitId, campusType = 'on', displayId = null) {
    const modal = document.getElementById('permitFilesModal');
    const modalTitle = document.getElementById('permitFilesModalTitle');
    const modalBody = document.getElementById('permitFilesModalBody');

    modalTitle.innerHTML = `<i class="fas fa-folder-open"></i> Permit Documents - ${escapeHtml(displayId || permitId)}`;
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

        let filesHtml = '<div style="display: block !important; text-align: left !important; width: 100% !important;">';

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

function closePermitFilesModal() {
    document.getElementById('permitFilesModal').classList.remove('active');
}
    
    function checkPasswordStrength() {
        const password = document.getElementById('new_password')?.value || '';
        const strengthMsg = document.getElementById('strengthMessage');
        if (!strengthMsg) return;
        
        if (password.length === 0) {
            strengthMsg.innerHTML = '';
            return;
        }
        
        if (password.length < 6) {
            strengthMsg.innerHTML = '🔴 Weak - Too short';
            strengthMsg.className = 'password-strength strength-weak';
        } else if (password.length < 10) {
            strengthMsg.innerHTML = '🟡 Medium - Could be stronger';
            strengthMsg.className = 'password-strength strength-medium';
        } else {
            strengthMsg.innerHTML = '🟢 Strong - Good password!';
            strengthMsg.className = 'password-strength strength-strong';
        }
    }
    
    function checkPasswordMatch() {
        const password = document.getElementById('new_password')?.value || '';
        const confirm = document.getElementById('confirm_password')?.value || '';
        const matchMsg = document.getElementById('matchMessage');
        if (!matchMsg) return;
        
        if (confirm.length === 0) {
            matchMsg.innerHTML = '';
            return;
        }
        
        if (password === confirm) {
            matchMsg.innerHTML = '✅ Passwords match';
            matchMsg.className = 'password-strength strength-strong';
        } else {
            matchMsg.innerHTML = '❌ Passwords do not match';
            matchMsg.className = 'password-strength strength-weak';
        }
    }
    
    window.onclick = function(event) {
        const reqModal = document.getElementById('requirementsModal');
        const accModal = document.getElementById('accomplishmentModal');
        const permitFilesModal = document.getElementById('permitFilesModal');
        if (event.target === reqModal) closeRequirementsModal();
        if (event.target === accModal) closeAccomplishmentModal();
        if (event.target === permitFilesModal) closePermitFilesModal();
                            }
    // showToast() is provided globally by js/enhancements.js

    <?php if ($password_change_success): ?>
        showToast(<?php echo json_encode('Password changed successfully!'); ?>, 'success');
    <?php endif; ?>

    <?php if ($password_change_error): ?>
        showToast(<?php echo json_encode($password_error_message); ?>, 'error');
    <?php endif; ?>
    // Locate REQUIREMENTS_BY_TYPE in your script tag:
const REQUIREMENTS_BY_TYPE = {
    on: {
        'Meeting/Fellowship': [
            { label: 'Activity Design', name: 'proposal', accept: '.pdf,.doc,.docx,.png,.jpg,.jpeg,image/png,image/jpeg' },
            { label: 'Others', name: 'others[]', accept: '.pdf,.doc,.docx,.png,.jpg,.jpeg,image/png,image/jpeg', multiple: true },
        ],
        'Seminar/Training/Forum': [
            { label: 'Activity Design', name: 'proposal', accept: '.pdf,.doc,.docx,.png,.jpg,.jpeg,image/png,image/jpeg' },
            { label: 'Others', name: 'others[]',accept: '.pdf,.doc,.docx,.png,.jpg,.jpeg,image/png,image/jpeg', multiple: true },
        ],
        'default': [
            { label: 'Activity Design', name: 'proposal', accept: '.pdf,.doc,.docx,.png,.jpg,.jpeg,image/png,image/jpeg' },
            { label: 'Others', name: 'others[]',accept: '.pdf,.doc,.docx,.png,.jpg,.jpeg,image/png,image/jpeg', multiple: true },
        ]
    },
    off: {
        'Meeting/Fellowship': [
           { label: 'Activity Design', name: 'proposal', accept: '.pdf,.doc,.docx,.png,.jpg,.jpeg,image/png,image/jpeg' },
            { label: 'Others', name: 'others[]',accept: '.pdf,.doc,.docx,.png,.jpg,.jpeg,image/png,image/jpeg', multiple: true },
        ],
        'default': [
            { label: 'Activity Design', name: 'proposal', accept: '.pdf,.doc,.docx,.png,.jpg,.jpeg,image/png,image/jpeg' },
            { label: 'Others', name: 'others[]',accept: '.pdf,.doc,.docx,.png,.jpg,.jpeg,image/png,image/jpeg', multiple: true },
        ]
    }
};
</script>

</body>
</html>