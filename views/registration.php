<?php

$org_info = getAuthenticatedOrg();
$client_email = $_SESSION['email'];
$user_id    = $org_info['acc_id'];
$first_name = $org_info['first_name'];
$last_name  = $org_info['last_name'];
$user_name  = trim($first_name . ' ' . $last_name);
$org_name   = $org_info['org_name'];
$org_status = $org_info['org_status'];
$contact_no = $org_info['contact_no'] ?? '';
$current_year = date('Y');
$today = date('Y-m-d');
$today_y = (int) date('Y');
$today_m = (int) date('n');
$today_d = (int) date('j');

// Parse existing start/end dates (for edit mode) into Y/M/D parts, defaulting to blank
function parseDateParts($dateStr) {
    if (!empty($dateStr) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateStr, $m)) {
        return ['y' => (int) $m[1], 'm' => (int) $m[2], 'd' => (int) $m[3]];
    }
    return ['y' => '', 'm' => '', 'd' => ''];
}
$start_parts = parseDateParts($edit_permit['start_date'] ?? '');
$end_parts   = parseDateParts($edit_permit['end_date'] ?? '');

$months_list = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
$year_range_end = $today_y + 5; // allow selecting up to 5 years ahead
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="design/reg_permit.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Activity Permit Registration | SOAU BSU</title>
    <style>
        .field-error {
            color: #991b1b;
            font-size: 0.8rem;
            margin-top: 4px;
            min-height: 1em;
        }
        input.invalid-date,
        select.invalid-date {
            border-color: #dc2626;
        }
        .date-dropdown-group {
            display: flex;
            gap: 8px;
        }
        .date-dropdown-group select {
            flex: 1;
            min-width: 0;
        }
    </style>
</head>
<body>
<?php
    if (file_exists("views/partials/nav_dash.php")) {
        include "views/partials/nav_dash.php";
    }
?>
<div class="portal-card">
    <header class="portal-header">
        <h1>Activity Permit Registration</h1>
        <p>Complete the form below to request an activity permit</p>
    </header>

    <div class="user-info-bar">
        <span>Logged in as: <span class="user-email"><?php echo htmlspecialchars($client_email); ?></span></span>
        <!-- <a href="logout.php" class="logout-link"> Logout →</a> -->
    </div>
    <?php if (!empty($_SESSION['permit_message'])): ?>
    <div style="padding: 12px 40px; background: #fee2e2; color: #991b1b; font-size: 0.875rem;">
        <?php echo htmlspecialchars($_SESSION['permit_message']); unset($_SESSION['permit_message']); ?>
    </div>
    <?php endif; ?>
    <div class="progress-bar">
        <span>Activity Permit Application</span>
        <div class="pdf-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="pdf-btn" onclick="window.open('premade/RSO Activity Permit (In-Campus).pdf', '_blank')">
                📄 View On-Campus Permit
            </button>
            <button type="button" class="pdf-btn" onclick="window.open('premade/RSO Activity Permit (Out-of-Campus).pdf', '_blank')">
                📄 View Off-Campus Permit
            </button>
        </div>
    </div>

    <div class="card-body">
        <form action="permit_register" method="POST" id="permitForm">

            <!-- Hidden field to pass email -->
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($client_email); ?>">
            <input type="hidden" name="campus_type" id="campus_type" value="">
            <input type="hidden" name="submit_permit" value="1">
            <input type="hidden" name="permit_id" value="<?php echo htmlspecialchars($edit_permit['id'] ?? ''); ?>">
            <input type="hidden" name="permit_type" value="<?php echo htmlspecialchars($edit_permit['campus_type'] ?? ''); ?>">

            <!-- Campus Type Selection -->
            <div class="form-section">
                <span class="section-label">Activity Type</span>
                <div class="campus-options">
                    <div class="campus-card on-campus" onclick="selectCampus('on')">
                        <div class="campus-icon"></div>
                        <div class="campus-title">On-Campus Activity</div>
                        <div class="campus-desc">Activity conducted within BSU premises</div>
                        <div class="campus-note requires">Standard processing: 3-5 business days</div>
                    </div>
                    <div class="campus-card off-campus" onclick="selectCampus('off')">
                        <div class="campus-icon"></div>
                        <div class="campus-title">Off-Campus Activity</div>
                        <div class="campus-desc">Activity conducted outside BSU premises</div>
                        <div class="campus-note requires">Additional requirements needed</div>
                    </div>
                </div>
            </div>

            <!-- Activity Permit Details -->
            <div class="form-section" id="activity-details" style="display: none;">
                <span class="section-label">Activity Permit Details</span>

                <div class="field-group full-width">
                    <label>Name of RSO / Organization <span class="required">*</span></label>
                    <input type="text" name="rso_name" id="rso_name" value="<?php echo htmlspecialchars($org_name); ?>" readonly required>
                </div>

                <div class="field-group full-width">
                    <label>Title of Activity <span class="required">*</span></label>
                    <input type="text" name="act_title" id="act_title" value="<?php echo htmlspecialchars($edit_permit['activity_title'] ?? ''); ?>" placeholder="Enter the full title of the activity" required>
                </div>

                <div class="field-group">
                    <label>Nature of Activity <span class="required">*</span></label>
                    <?php
                        $current_nature = $edit_permit['nature_of_activity'] ?? '';
                        $fixed_natures = ['Meeting/Fellowship','Maintenance/Cleaning','Seminar/Training/Forum','Socialization','Contest/Competition','Extension/Outreach','Campaign/Recruitment','Income Generating Activity','Collection of Fees/Fines'];
                        $is_other_nature = $current_nature !== '' && !in_array($current_nature, $fixed_natures);
                    ?>
                    <select id="act_type" name="act_type" required onchange="toggleOtherNature()">
                        <option value="" disabled <?php echo $current_nature === '' ? 'selected' : ''; ?>>Select Nature of Activity...</option>
                        <?php foreach ($fixed_natures as $nature): ?>
                            <option value="<?php echo $nature; ?>" <?php echo $current_nature === $nature ? 'selected' : ''; ?>><?php echo $nature; ?></option>
                        <?php endforeach; ?>
                        <option value="Others" <?php echo $is_other_nature ? 'selected' : ''; ?>>Others</option>
                    </select>
                    <div id="others_container" style="<?php echo $is_other_nature ? '' : 'display:none;'; ?> margin-top: 10px;">
                        <input type="text" name="other_text" id="other_text" value="<?php echo $is_other_nature ? htmlspecialchars($current_nature) : ''; ?>" placeholder="Please specify the nature of activity...">
                    </div>
                </div>

                <div class="field-group">
                    <label>Objectives <span class="required">*</span></label>
                    <input type="text" name="objectives1" id="objectives1" placeholder="Objective 1" style="margin-bottom:5px;" value="<?php echo htmlspecialchars($edit_permit['objective1'] ?? ''); ?>" required>
                    <input type="text" name="objectives2" id="objectives2" placeholder="Objective 2" style="margin-bottom:5px;" value="<?php echo htmlspecialchars($edit_permit['objective2'] ?? ''); ?>">
                    <input type="text" name="objectives3" id="objectives3" placeholder="Objective 3" value="<?php echo htmlspecialchars($edit_permit['objective3'] ?? ''); ?>">
                    <div class="hint-text">Add at least one objective for the activity</div>
                </div>

                <div class="form-grid">
                    <div class="field-group">
                        <label>Start Date <span class="required">*</span></label>
                        <div class="date-dropdown-group">
                            <select id="act_start_month" class="date-part" onchange="updateDatePart('act_start')" required>
                                <option value="" disabled <?php echo $start_parts['m'] === '' ? 'selected' : ''; ?>>Month</option>
                                <?php foreach ($months_list as $num => $label): ?>
                                    <option value="<?php echo $num; ?>" <?php echo $start_parts['m'] === $num ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="act_start_day" class="date-part" onchange="updateDatePart('act_start')" required>
                                <option value="" disabled <?php echo $start_parts['d'] === '' ? 'selected' : ''; ?>>Day</option>
                                <?php for ($d = 1; $d <= 31; $d++): ?>
                                    <option value="<?php echo $d; ?>" <?php echo $start_parts['d'] === $d ? 'selected' : ''; ?>><?php echo $d; ?></option>
                                <?php endfor; ?>
                            </select>
                            <select id="act_start_year" class="date-part" onchange="updateDatePart('act_start')" required>
                                <option value="" disabled <?php echo $start_parts['y'] === '' ? 'selected' : ''; ?>>Year</option>
                                <?php for ($y = $today_y; $y <= $year_range_end; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $start_parts['y'] === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <input type="hidden" name="act_start" id="act_start" value="<?php echo htmlspecialchars($edit_permit['start_date'] ?? ''); ?>">
                        <div class="field-error" id="act_start_error"></div>
                    </div>

                    <div class="field-group">
                        <label>End Date <span class="required">*</span></label>
                        <div class="date-dropdown-group">
                            <select id="act_end_month" class="date-part" onchange="updateDatePart('act_end')" required>
                                <option value="" disabled <?php echo $end_parts['m'] === '' ? 'selected' : ''; ?>>Month</option>
                                <?php foreach ($months_list as $num => $label): ?>
                                    <option value="<?php echo $num; ?>" <?php echo $end_parts['m'] === $num ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="act_end_day" class="date-part" onchange="updateDatePart('act_end')" required>
                                <option value="" disabled <?php echo $end_parts['d'] === '' ? 'selected' : ''; ?>>Day</option>
                                <?php for ($d = 1; $d <= 31; $d++): ?>
                                    <option value="<?php echo $d; ?>" <?php echo $end_parts['d'] === $d ? 'selected' : ''; ?>><?php echo $d; ?></option>
                                <?php endfor; ?>
                            </select>
                            <select id="act_end_year" class="date-part" onchange="updateDatePart('act_end')" required>
                                <option value="" disabled <?php echo $end_parts['y'] === '' ? 'selected' : ''; ?>>Year</option>
                                <?php for ($y = $today_y; $y <= $year_range_end; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $end_parts['y'] === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <input type="hidden" name="act_end" id="act_end" value="<?php echo htmlspecialchars($edit_permit['end_date'] ?? ''); ?>">
                        <div class="field-error" id="act_end_error"></div>
                    </div>

                    <div class="field-group">
                        <label>Start Time <span class="required">*</span></label>
                        <input type="time" name="time_start" id="time_start" value="<?php echo htmlspecialchars($edit_permit['time_start'] ?? ''); ?>" required>
                    </div>
                    <div class="field-group">
                        <label>End Time <span class="required">*</span></label>
                        <input type="time" name="time_end" id="time_end" value="<?php echo htmlspecialchars($edit_permit['time_end'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="field-group">
                    <label>Venue <span class="required">*</span></label>
                    <input type="text" name="act_venue" id="act_venue" placeholder="Specify the exact venue/location" value="<?php echo htmlspecialchars($edit_permit['venue'] ?? ''); ?>" required>
                </div>

                <div class="field-group">
                    <label>RSO Adviser Name <span class="required">*</span></label>
                    <input type="text" name="adviser_name" id="adviser_name" placeholder="Full name of RSO Adviser"
                        value="<?php echo htmlspecialchars($edit_permit['adviser'] ?? ''); ?>"
                        pattern="[A-Za-zÀ-ÿ\s.'-]+"
                        title="Please enter letters only (no numbers or special characters, except . ' -)"
                        oninput="this.value = this.value.replace(/[^A-Za-zÀ-ÿ\s.'-]/g, '')"
                        required>
                </div>
            </div>

            <!-- Off-Campus Additional Requirements -->
            <div id="off-campus-requirements" class="sub-form" style="display: none;">
                <span class="section-label">Off-Campus Requirements</span>
                <div class="warning-box">
                    ⚠️ Off-campus activities require additional processing time (7-10 business days) and extra documentation.
                </div>
                <div class="field-group">
                    <label>Off-Campus Venue Address <span class="required">*</span></label>
                    <input type="text" name="off_campus_address" id="off_campus_address" placeholder="Complete address of off-campus venue" value="<?php echo htmlspecialchars($edit_permit['off_campus_address'] ?? ''); ?>">
                </div>
                <div class="field-group">
                    <label>Transportation Arrangement</label>
                    <textarea name="transportation" id="transportation" rows="2" placeholder="Describe transportation plan for participants"><?php echo htmlspecialchars($edit_permit['transportation'] ?? ''); ?></textarea>
                </div>
                <div class="field-group">
                    <label>Safety and Security Plan <span class="required">*</span></label>
                    <textarea name="safety_plan" id="safety_plan" rows="3" placeholder="Describe safety measures, emergency procedures, and risk management plan"><?php echo htmlspecialchars($edit_permit['safety_plan'] ?? ''); ?></textarea>
                </div>
                <div class="field-group">
                    <label>Parent/Guardian Consent Required?</label>
                    <select name="parent_consent" id="parent_consent">
                        <option value="no" <?php echo ($edit_permit['parent_consent'] ?? 'no') === 'no' ? 'selected' : ''; ?>>No</option>
                        <option value="yes" <?php echo ($edit_permit['parent_consent'] ?? '') === 'yes' ? 'selected' : ''; ?>>Yes - Parent/Guardian consent needed</option>
                    </select>
                </div>
            </div>

            <div class="portal-footer">
                <a href="dashboard_organization" class="btn btn-outline">← Cancel</a>
                <button type="button" class="btn btn-view" onclick="viewPermitPDF()"> View Permit</button>
                <button type="button" class="btn btn-primary" id="downloadBtn" onclick="downloadPermitPDF()" style="display: none;">⬇️ Download</button>
                <button type="button" class="btn btn-success" onclick="submitApplication()"> Submit Application</button>
            </div>
        </form>
    </div>
</div>

<!-- PDF Modal -->
<div id="pdfModal" class="pdf-modal">
    <div class="pdf-modal-content">
        <div class="pdf-modal-header">
            <h3> Activity Permit Preview</h3>
            <button class="close-pdf-modal" onclick="closePDFModal()">&times;</button>
        </div>
        <div class="pdf-modal-body">
            <iframe id="pdfFrame" src=""></iframe>
        </div>
        <div class="modal-buttons">
            <button class="btn btn-outline" onclick="closePDFModal()">Close</button>
            <!-- //<button class="btn btn-primary" id="modalDownloadBtn" onclick="downloadCurrentPDF()">⬇ Download PDF</button> -->
        </div>
    </div>
</div>

<script>
let selectedCampus = null;
let currentPDFBlob = null;

// Single source of truth for "today" — comes from PHP server date, avoids UTC/local mismatch
const todayStr = "<?php echo $today; ?>";

document.addEventListener('DOMContentLoaded', function () {
    // Build hidden field values from any pre-filled dropdowns (edit mode)
    updateDatePart('act_start');
    updateDatePart('act_end');

    <?php if (!empty($edit_permit['campus_type'])): ?>
        selectCampus('<?php echo $edit_permit['campus_type']; ?>');
    <?php endif; ?>
});

function daysInMonth(year, month) {
    // month is 1-12
    return new Date(year, month, 0).getDate();
}

// Rebuilds the Day dropdown's options to match the number of days in the selected month/year,
// preserving the currently selected day if it's still valid.
function refreshDayOptions(prefix) {
    const monthSel = document.getElementById(prefix + '_month');
    const yearSel = document.getElementById(prefix + '_year');
    const daySel = document.getElementById(prefix + '_day');

    const month = parseInt(monthSel.value, 10);
    const year = parseInt(yearSel.value, 10);

    if (!month || !year) return; // wait until both are chosen

    const maxDay = daysInMonth(year, month);
    const currentSelection = parseInt(daySel.value, 10);

    const existingCount = daySel.querySelectorAll('option[value]:not([value=""])').length;
    if (existingCount !== maxDay) {
        const placeholder = daySel.querySelector('option[value=""]');
        daySel.innerHTML = '';
        if (placeholder) daySel.appendChild(placeholder);
        for (let d = 1; d <= maxDay; d++) {
            const opt = document.createElement('option');
            opt.value = d;
            opt.textContent = d;
            daySel.appendChild(opt);
        }
        if (currentSelection && currentSelection <= maxDay) {
            daySel.value = currentSelection;
        } else if (currentSelection) {
            daySel.value = maxDay;
        }
    }
}

// Reads the month/day/year dropdowns for the given prefix ('act_start' or 'act_end'),
// rebuilds the day list if needed, writes the combined value into the hidden input,
// and runs validation.
function updateDatePart(prefix) {
    const monthSel = document.getElementById(prefix + '_month');
    const daySel = document.getElementById(prefix + '_day');
    const yearSel = document.getElementById(prefix + '_year');
    const hiddenInput = document.getElementById(prefix);

    refreshDayOptions(prefix);

    const month = monthSel.value;
    const day = daySel.value;
    const year = yearSel.value;

    if (month && day && year) {
        const mm = String(month).padStart(2, '0');
        const dd = String(day).padStart(2, '0');
        hiddenInput.value = `${year}-${mm}-${dd}`;
    } else {
        hiddenInput.value = '';
    }

    validateDateGroup(prefix);
}

function validateDateGroup(prefix) {
    const errorDiv = document.getElementById(prefix + '_error');
    const hiddenInput = document.getElementById(prefix);
    const monthSel = document.getElementById(prefix + '_month');
    const daySel = document.getElementById(prefix + '_day');
    const yearSel = document.getElementById(prefix + '_year');

    const startVal = document.getElementById('act_start').value;
    const endVal = document.getElementById('act_end').value;
    let message = '';

    if (hiddenInput.value) {
        if (hiddenInput.value < todayStr) {
            message = 'This date cannot be in the past.';
        } else if (prefix === 'act_end' && startVal && hiddenInput.value < startVal) {
            message = 'End date cannot be before the start date.';
        }
    }

    [monthSel, daySel, yearSel].forEach(el => el.classList.toggle('invalid-date', !!message));
    errorDiv.textContent = message;

    // If start date changed, re-check end date's validity too
    if (prefix === 'act_start' && endVal) {
        const endErrorDiv = document.getElementById('act_end_error');
        let endMessage = '';
        if (endVal < todayStr) {
            endMessage = 'This date cannot be in the past.';
        } else if (startVal && endVal < startVal) {
            endMessage = 'End date cannot be before the start date.';
        }
        const endSelects = [document.getElementById('act_end_month'), document.getElementById('act_end_day'), document.getElementById('act_end_year')];
        endSelects.forEach(el => el.classList.toggle('invalid-date', !!endMessage));
        endErrorDiv.textContent = endMessage;
    }
}

function selectCampus(type) {
    selectedCampus = type;
    document.getElementById('campus_type').value = type;

    const onCard = document.querySelector('.campus-card.on-campus');
    const offCard = document.querySelector('.campus-card.off-campus');

    if (type === 'on') {
        onCard.classList.add('selected');
        offCard.classList.remove('selected');
        document.getElementById('off-campus-requirements').style.display = 'none';
    } else {
        offCard.classList.add('selected');
        onCard.classList.remove('selected');
        document.getElementById('off-campus-requirements').style.display = 'block';
    }

    document.getElementById('activity-details').style.display = 'block';
    toggleOffCampusRequired(type === 'off');
}

function toggleOffCampusRequired(isOffCampus) {
    const offCampusFields = document.querySelectorAll('#off-campus-requirements input, #off-campus-requirements textarea, #off-campus-requirements select');

    offCampusFields.forEach(field => {
        if (isOffCampus && field.name !== 'transportation') {
            field.setAttribute('required', '');
        } else {
            field.removeAttribute('required');
        }
    });
}

function toggleOtherNature() {
    var select = document.getElementById("act_type");
    var container = document.getElementById("others_container");
    container.style.display = (select.value === "Others") ? "block" : "none";

    var otherInput = container.querySelector('input');
    if (select.value === "Others") {
        otherInput.setAttribute('required', '');
    } else {
        otherInput.removeAttribute('required');
    }
}

function validateFormData() {
    // Validate campus selection
    if (!selectedCampus) {
        alert("Please select whether this is an On-Campus or Off-Campus activity.");
        return false;
    }

    // Validate required fields
    const requiredFields = ['rso_name', 'act_title', 'act_type', 'objectives1', 'act_start', 'act_end', 'time_start', 'time_end', 'act_venue', 'adviser_name'];

    for (let fieldId of requiredFields) {
        const field = document.getElementById(fieldId);
        if (!field || !field.value || field.value.trim() === '') {
            const label = field.previousElementSibling?.innerText || fieldId;
            alert(`Please fill out: ${label}`);
            field?.focus();
            return false;
        }
    }

    // Validate dates: not in the past, end >= start
    const startDate = document.getElementById('act_start').value;
    const endDate = document.getElementById('act_end').value;

    if (startDate.split('-')[0].length !== 4 || endDate.split('-')[0].length !== 4) {
        alert("Please enter a valid 4-digit year.");
        return false;
    }
    if (startDate < todayStr) {
        alert("Start date cannot be in the past.");
        document.getElementById('act_start').focus();
        return false;
    }
    if (endDate < todayStr) {
        alert("End date cannot be in the past.");
        document.getElementById('act_end').focus();
        return false;
    }
    if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
        alert("End date must be after or equal to start date.");
        return false;
    }

    return true;
}

async function viewPermitPDF() {
    if (!validateFormData()) {
        return;
    }

    const campusType = document.getElementById('campus_type').value;

    if (!campusType) {
        alert("Please select On-Campus or Off-Campus activity type first.");
        return;
    }

    const modal = document.getElementById('pdfModal');
    const iframe = document.getElementById('pdfFrame');
    iframe.src = 'about:blank';
    modal.classList.add('active');

    const formData = new FormData();
    formData.append('rso_name', document.getElementById('rso_name').value);
    formData.append('act_title', document.getElementById('act_title').value);
    formData.append('act_type', document.getElementById('act_type').value);
    formData.append('objectives1', document.getElementById('objectives1').value);
    formData.append('objectives2', document.getElementById('objectives2').value);
    formData.append('objectives3', document.getElementById('objectives3').value);
    formData.append('act_start', document.getElementById('act_start').value);
    formData.append('act_end', document.getElementById('act_end').value);
    formData.append('time_start', document.getElementById('time_start').value);
    formData.append('time_end', document.getElementById('time_end').value);
    formData.append('act_venue', document.getElementById('act_venue').value);
    formData.append('adviser_name', document.getElementById('adviser_name').value);
    formData.append('email', '<?php echo htmlspecialchars($client_email); ?>');
    formData.append('campus_type', campusType);

    formData.append('client_name', document.getElementById('rso_name').value);
    formData.append('id_number', '');
    formData.append('contact_number', '');
    formData.append('position', '');

    const otherText = document.getElementById('other_text');
    if (otherText && otherText.value) {
        formData.append('other_text', otherText.value);
    }

    if (campusType === 'off') {
        const offAddress = document.getElementById('off_campus_address');
        const safetyPlan = document.getElementById('safety_plan');
        if (offAddress) formData.append('off_campus_address', offAddress.value);
        if (safetyPlan) formData.append('safety_plan', safetyPlan.value);
    }

    try {
        const response = await fetch('/org_track/helpers/generate_permit.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const blob = await response.blob();
        currentPDFBlob = blob;
        const url = URL.createObjectURL(blob);
        iframe.src = url;
    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Error generating PDF: ' + error.message);
        closePDFModal();
    }
}

function downloadCurrentPDF() {
    if (currentPDFBlob) {
        const url = URL.createObjectURL(currentPDFBlob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Activity_Permit_${document.getElementById('rso_name').value.replace(/\s/g, '_')}.pdf`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    } else {
        alert('Please view the permit first before downloading.');
    }
}

function downloadPermitPDF() {
    if (currentPDFBlob) {
        const url = URL.createObjectURL(currentPDFBlob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Activity_Permit_${document.getElementById('rso_name').value.replace(/\s/g, '_')}.pdf`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    } else {
        alert('Please generate the permit first using the View button.');
    }
}

function closePDFModal() {
    const modal = document.getElementById('pdfModal');
    const iframe = document.getElementById('pdfFrame');
    iframe.src = 'about:blank';
    modal.classList.remove('active');
    if (currentPDFBlob) {
        URL.revokeObjectURL(currentPDFBlob);
        currentPDFBlob = null;
    }
}

function submitApplication() {
    if (!validateFormData()) {
        return;
    }

    const activityTitle = document.getElementById('act_title').value;
    const campusText = selectedCampus === 'on' ? 'On-Campus' : 'Off-Campus';
    const confirmMsg = `Please confirm your activity permit application:\n\n` +
                       `Activity: ${activityTitle}\n` +
                       `Type: ${campusText} Activity\n` +
                       `Organization: ${document.getElementById('rso_name').value}\n\n` +
                       `Do you want to submit this application for approval?`;

    if (confirm(confirmMsg)) {
        const form = document.getElementById('permitForm');
        form.submit();
    }
}
</script>

</body>
</html>