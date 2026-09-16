<?php
include "views/partials/nav.php"; 

$registration_error   = $_SESSION['registration_error']   ?? $_SESSION['error'] ?? null;
$registration_success = $_SESSION['registration_success'] ?? null;
$login_email          = $_SESSION['email'] ?? null;

// Grab org_data passed directly from session redirect, or query model fallback
$org_data = $_SESSION['org_data'] ?? null;

if (!$org_data && $login_email && isset($this->model)) {
    $org_data = $this->model->getOrgByEmail($login_email);
}

// Check if this is an update/renewal mode
$is_update = !empty($org_data);

// Dynamic text labels based on state
$page_title      = $is_update ? "Update Organization Details" : "Register Organization";
$header_title    = $is_update ? "Recognized Student Organization (RSO) Renewal & Update" : "Recognized Student Organization (RSO) Registration";
$header_subtitle = $is_update ? "Update your details and submit required compliance documents" : "Complete the form below to initiate your SOAU registration";
$portal_bar      = $is_update ? "Organization Renewal Portal" : "Organization Registration Portal";
$submit_btn_text = $is_update ? "Submit Update →" : "Submit Registration →";

// Clean up temporary alert messages and redirect state session data
unset($_SESSION['registration_error'], $_SESSION['registration_success'], $_SESSION['error'], $_SESSION['org_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="design/reg_style.css" rel="stylesheet">
    <title><?php echo $page_title; ?> | BSU ORG-Track</title>
</head>
<body>

<?php 
    if(file_exists("navbar.php")) { 
        include "navbar.php"; 
    }
?>

<div class="portal-card">
    <header class="portal-header">
        <h1><?php echo $header_title; ?></h1>
        <p><?php echo $header_subtitle; ?></p>
    </header>
    <div class="progress-bar"><?php echo $portal_bar; ?></div>

    <div class="card-body">

        <?php if ($registration_error): ?>
            <div class="alert alert-error" style="background:#fdecea;color:#611a15;border:1px solid #f5c6cb;padding:12px 16px;border-radius:6px;margin-bottom:20px;">
                <?php echo htmlspecialchars($registration_error); ?>
            </div>
        <?php endif; ?>

        <?php if ($registration_success): ?>
            <div class="alert alert-success" style="background:#eafaf0;color:#0f5132;border:1px solid #badbcc;padding:12px 16px;border-radius:6px;margin-bottom:20px;">
                <?php echo htmlspecialchars($registration_success); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo $this->url ?>/org_registration" method="POST" enctype="multipart/form-data">
            
            <!-- Hidden field to flag mode in backend controller -->
            <input type="hidden" name="is_update" value="<?php echo $is_update ? '1' : '0'; ?>">

            <!-- Organization Information Section -->
            <div class="form-section">
                <span class="section-label">Organization Information</span>
                <div class="form-grid">
                    <div class="field-group full-width">
                        <label>Organization Name <span class="required">*</span></label>
                        <input type="text" name="org_name" 
                            value="<?php echo htmlspecialchars($org_data['org_name'] ?? ''); ?>" 
                            placeholder="Enter full organization name" 
                            pattern="[A-Za-z\s]+"  
                            oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                            title="Only letters and spaces are allowed" 
                            required>
                    </div>

                    <div class="field-group full-width">
                        <label for="type">Organization College <span class="required">*</span></label>
                        <input 
                            list="org_types" 
                            id="type" 
                            name="type" 
                            value="<?php echo htmlspecialchars($org_data['org_type'] ?? ''); ?>"
                            placeholder="Select or type Organization type..." 
                            pattern="[A-Za-z\s]+" 
                            oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"     
                            required
                            autocomplete="off"
                        >
                        <datalist id="org_types">
                            <option value="CIS" label="College of Information Sciences (CIS)"></option>
                            <option value="CF" label="College of Forestry (CF)"></option>
                        </datalist>
                    </div>

                    <div class="field-group">
                        <label>Officer Last Name <span class="required">*</span></label>
                        <input type="text" name="lname" 
                               value="<?php echo htmlspecialchars($org_data['last_name'] ?? ''); ?>" 
                               pattern="[A-Za-z\s]+" 
                               oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                               placeholder="Last name of Officer" required>
                    </div>

                    <div class="field-group">
                        <label>Officer First Name <span class="required">*</span></label>
                        <input type="text" name="fname" 
                               value="<?php echo htmlspecialchars($org_data['first_name'] ?? ''); ?>" 
                               pattern="[A-Za-z\s]+" 
                               oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                               placeholder="First name of Officer" required>
                    </div>
                    <div class="field-group">
                        <label>Officer Middle Name <span class="required">*</span></label>
                        <input type="text" name="mname" 
                               value="<?php echo htmlspecialchars($org_data['middle_name'] ?? ''); ?>" 
                               pattern="[A-Za-z\s]+" 
                               oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                               placeholder="Middle name of Officer" required>
                    </div>


                    <div class="field-group">
                        <label>Contact Number <span class="required">*</span></label>

                                    <input 
                                type="tel" name="contact" 
                                id="phone" 
                                placeholder="09XX-XXX-XXXX" 
                                maxlength="13" 
                                pattern="09\d{2}-\d{3}-\d{4}" 
                                value="<?php echo htmlspecialchars($org_data['contact_no'] ?? ''); ?>" 
                               placeholder="09XX-XXX-XXXX" pattern="[0-9]{4}-[0-9]{3}-[0-9]{4}"
                                required>
                    </div>

                    <div class="field-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" id="email_field"
                                value="<?php echo htmlspecialchars($login_email ?? ''); ?>" 
                                placeholder="organization@gmail.com" 
                                onkeydown="autocompleteGmail(event, this)"
                                onblur="appendGmailOnBlur(this)"
                                <?php echo $is_update ? 'readonly' : ''; ?> required>
                        </div>

                    <div class="field-group">
                        <label>Organization Status <span class="required">*</span></label>
                        <select id="org_type" name="org_type" required>
                            <option value="" disabled <?php echo !$is_update ? 'selected' : ''; ?>>Select registration type...</option>
                            <option value="existing" <?php echo $is_update ? 'selected' : ''; ?>>Existing Organization (Renewal/Update)</option>
                            <option value="new" <?php echo (!$is_update && isset($org_data['org_status']) && strtolower($org_data['org_status']) === 'new') ? 'selected' : ''; ?>>New Organization (First-time Registration)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Document Requirements Section -->
            <div class="form-section">
                <span class="section-label">Document Requirements</span>
                <div class="file-list">
                    <div class="file-item">
                        <span class="file-badge">Required</span>
                        <span>1. Letter of Application (PDF, JPG, PNG)</span>
                    </div>
                    <div class="file-item">
                        <span class="file-badge">Required</span>
                        <span>2. Accomplished Application Form (PDF, JPG, PNG)</span>
                    </div>
                    <div class="file-item">
                        <span class="file-badge">Required</span>
                        <span>3. Photocopy of BSU ID Cards (PDF, JPG, PNG)</span>
                    </div>
                    <div class="file-item">
                        <span class="file-badge">Required</span>
                        <span>4. Tentative Action Plan (PDF, JPG, PNG)</span>
                    </div>
                    <div class="file-item">
                        <span class="file-badge">Required</span>
                        <span>5. Ratified Constitutions and By-Laws (PDF, JPG, PNG)</span>
                    </div>
                </div>

                <div class="form-grid" style="margin-top: 20px;">
                    <div class="field-group full-width">
                        <label>1. Letter of Application (LOA) <span class="required">*</span></label>
                        <input type="file" name="LOA" accept=".pdf, .jpg, .jpeg, .png" required>
                    </div>
                    <div class="field-group full-width">
                        <label>2. Accomplished Application Form (AAF) <span class="required">*</span></label>
                        <input type="file" name="AAF" accept=".pdf, .jpg, .jpeg, .png" required>
                    </div>
                    <div class="field-group full-width">
                        <label>3. Photocopy of BSU ID Cards (POBIC) <span class="required">*</span></label>
                        <input type="file" name="POBIC" accept=".pdf, .jpg, .jpeg, .png" required>
                    </div>
                    <div class="field-group full-width">
                        <label>4. Tentative Action Plan (TAP) <span class="required">*</span></label>
                        <input type="file" name="TAP" accept=".pdf, .jpg, .jpeg, .png" required>
                    </div>
                    <div class="field-group full-width">
                        <label>5. Ratified Constitutions and By-Laws (RCAB) <span class="required">*</span></label>
                        <input type="file" name="RCAB" accept=".pdf, .jpg, .jpeg, .png" required>
                    </div>
                </div>
            </div>

            <!-- Additional Requirements for New Organizations -->
            <div id="new_org_fields" class="sub-form" style="display: none;">
                <span class="section-label" style="margin-top: 0;">Additional Requirements for New Organizations</span>
                <div class="field-group full-width">
                    <label>Description of Proposed Organization <span class="required">*</span></label>
                    <textarea name="org_description" rows="4" placeholder="Describe the purpose, goals, and objectives of your organization..."></textarea>
                </div>
                <div class="field-group full-width">
                    <label>Description of Institution & SEC Registration Details</label>
                    <input type="text" name="institution_description" placeholder="Provide SEC registration info or institutional details (if applicable)">
                </div>
            </div>

            <div class="portal-footer">
                <a href="home" class="btn btn-outline">← Back</a>
                <button type="submit" class="btn btn-primary" onclick="return confirmAction()"><?php echo $submit_btn_text; ?></button>
            </div>
        </form>
    </div>
</div>

<script>
    function autocompleteGmail(e, input) {
    // If user presses Tab or Enter and hasn't added an '@' domain yet
    if ((e.key === 'Tab' || e.key === 'Enter') && input.value && !input.value.includes('@')) {
        input.value = input.value.trim() + '@gmail.com';
    }
}

function appendGmailOnBlur(input) {
    // Also auto-appends if they click away from the input
    if (input.value && !input.value.includes('@')) {
        input.value = input.value.trim() + '@gmail.com';
    }
}
    const phoneInput = document.getElementById('phone');

  phoneInput.addEventListener('input', (e) => {
    // 1. Strip all non-digit characters
    let digits = e.target.value.replace(/\D/g, '');

    // 2. Ensure it starts with '09'
    if (digits.length >= 2 && !digits.startsWith('09')) {
      digits = '09' + digits.slice(2);
    }

    // 3. Apply the 09XX-XXX-XXXX formatting
    let formatted = '';
    if (digits.length > 0) {
      formatted += digits.substring(0, 4);
    }
    if (digits.length >= 5) {
      formatted += '-' + digits.substring(4, 7);
    }
    if (digits.length >= 8) {
      formatted += '-' + digits.substring(7, 11);
    }

    // 4. Update the input value
    e.target.value = formatted;
  });
    const isUpdateMode = <?php echo json_encode($is_update); ?>;

    function toggleNewOrgFields() {
        const typeSelect = document.getElementById('org_type');
        const newOrgDiv = document.getElementById('new_org_fields');
        const isNewOrg = typeSelect.value === 'new';
        
        newOrgDiv.style.display = isNewOrg ? 'block' : 'none';
        
        const descTextarea = document.querySelector('textarea[name="org_description"]');
        if (isNewOrg) {
            if (descTextarea) descTextarea.setAttribute('required', '');
        } else {
            if (descTextarea) descTextarea.removeAttribute('required');
        }
    }
    
    function confirmAction() {
    const form = document.querySelector('form');
    const emailInput = document.getElementById('email_field');

    // Normalize email before validating — don't rely on blur/keydown having fired
    if (!emailInput.readOnly && emailInput.value && !emailInput.value.includes('@')) {
        emailInput.value = emailInput.value.trim() + '@gmail.com';
    }

    // Force native HTML5 validation to run NOW, and show the browser's
    // own red-outline/tooltip UI on the first invalid field if it fails.
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }

    // Your existing manual checks can stay, but they're now a backstop,
    // not the only thing actually enforced
    const orgType = document.getElementById('org_type').value;
    if (!orgType) {
        alert("Please select an organization status type.");
        return false;
    }

    const fileInputs = document.querySelectorAll('input[type="file"][required]');
    for (let input of fileInputs) {
        if (!input.files || input.files.length === 0) {
            alert("Please upload all required documents.");
            return false;
        }
    }

    const orgName = document.querySelector('input[name="org_name"]').value;
    const actionWord = isUpdateMode ? "update details for" : "register";
    const confirmMsg = `Are you sure you want to ${actionWord} "${orgName}"?\n\nPlease review all information before submitting. Thank you.`;

    return confirm(confirmMsg);
}
    
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('org_type');
        if (typeSelect) {
            typeSelect.addEventListener('change', toggleNewOrgFields);
            toggleNewOrgFields();
        }
    });
</script>

</body>
</html>