<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register SOAU Staff | BSU ORG-Track</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="design/reg_staff_style.css" rel="stylesheet">
    <style>
        .required-asterisk { color: #d9534f; font-weight: 700; }
    </style>
</head>
<body>

<?php 
    if (file_exists("views/partials/nav.php")) { 
        include "views/partials/nav.php"; 
    }
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>Register as SOAU Staff</h1>
            <p>Create your staff account for Student Organizations and Affairs Unit</p>
        </div>
        <div class="card-body">

            <!-- Flash Error Message -->
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #f5c6cb;">
                    <?php 
                        echo htmlspecialchars($_SESSION['error_message']); 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Flash Success Message -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #c3e6cb;">
                    <?php 
                        echo htmlspecialchars($_SESSION['success_message']); 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php else: ?>
                <!-- <div class="alert alert-info" style="margin-bottom: 15px;">
                     Staff accounts require a valid @bsu.edu.ph email address.
                </div> -->
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($this->url ?? ''); ?>/staff_registration" method="POST" onsubmit="return validateStaffRegistrationForm()">
                <div class="form-group">
                    <label>Last Name <span class="required-asterisk">*</span></label>
                    <input type="text" name="lname" id="lname" required placeholder="Enter your Last name" pattern="[A-Za-z\s'\-]+" title="Letters only" oninput="restrictToLetters(this)">
                </div>
                <div class="form-group">
                    <label>First Name <span class="required-asterisk">*</span></label>
                    <input type="text" name="fname" id="fname" required placeholder="Enter your First name" pattern="[A-Za-z\s'\-]+" title="Letters only" oninput="restrictToLetters(this)">
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="mname" id="mname" placeholder="Enter your Middle name (optional)" pattern="[A-Za-z\s'\-]*" title="Letters only" oninput="restrictToLetters(this)">
                </div>
                <div class="form-group">
                    <label>Email Address <span class="required-asterisk">*</span></label>
                    <input type="email" name="email" required placeholder="name@bsu.edu.ph" pattern=".+@bsu\.edu\.ph" title="Must end with @bsu.edu.ph">
                    <small style="color: var(--text-muted);">Must end with @bsu.edu.ph</small>
                </div>
                <div class="form-group">
                    <label>Employee/Staff ID <span class="required-asterisk">*</span></label>
                    <input type="text" name="staff_id" id="staff_id" required placeholder="6-digit staff ID" inputmode="numeric" maxlength="6" pattern="\d{6}" title="Must be exactly 6 digits" oninput="restrictToDigits(this, 6)">
                    <small id="staff_id_error" style="color: #d9534f; display: none;">Staff ID must be exactly 6 digits.</small>
                </div>
                <div class="form-group">
                    <label>Contact Number <span class="required-asterisk">*</span></label>
                    <input type="text" name="contact_no" id="contact_no" required placeholder="0911-111-1111" inputmode="numeric" maxlength="13" title="Format: 0911-111-1111" oninput="formatContactNumber(this)">
                    <small id="contact_no_error" style="color: #d9534f; display: none;">Please enter a valid contact number (e.g. 0911-111-1111).</small>
                </div>
                <div class="form-group">
                    <label>Create Password <span class="required-asterisk">*</span></label>
                    <input type="password" id="password" name="password" minlength="8" required placeholder="Min. 8 characters" oninput="checkPasswordStrength()">
                    <ul id="passwordChecklist" style="list-style: none; padding: 0; margin: 8px 0 0; font-size: 0.8rem; line-height: 1.7; display: none;">
                        <li id="req-length">At least 8 characters</li>
                        <li id="req-upper">One uppercase letter</li>
                        <li id="req-lower">One lowercase letter</li>
                        <li id="req-number">One number</li>
                        <li id="req-special">One special character (e.g. ! @ # $)</li>
                    </ul>
                    <small id="password_requirements_error" style="color: #d9534f; display: none;">Please meet all password requirements above.</small>
                </div>
                <div class="form-group">
                    <label>Confirm Password <span class="required-asterisk">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="8" required placeholder="Confirm your password" oninput="checkPasswordMatch()">
                    <small id="password_match_error" style="color: #d9534f; display: none;">Passwords do not match.</small>
                </div>
                <button type="submit" class="btn">Register Staff Account →</button>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="home" style="color: var(--staff-color);">← Back to Sign In</a>
            </div>
        </div>
    </div>
</div>

<script>
function restrictToLetters(input) {
    input.value = input.value.replace(/[^A-Za-z\s'-]/g, '');
}

function restrictToDigits(input, maxLen) {
    input.value = input.value.replace(/\D/g, '').slice(0, maxLen);
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

function setRequirement(id, passed, label) {
    const el = document.getElementById(id);
    el.textContent = (passed ? '✅ ' : '⬜ ') + label;
    el.style.color = passed ? '#155724' : '#6c757d';
}

function showPasswordChecklist() {
    document.getElementById('passwordChecklist').style.display = 'block';
}

function checkPasswordStrength() {
    const password = document.getElementById('password').value;

    // Only show the checklist once there's something to check.
    if (password.length > 0) {
        showPasswordChecklist();
    }

    const checks = {
        length:  password.length >= 8,
        upper:   /[A-Z]/.test(password),
        lower:   /[a-z]/.test(password),
        number:  /[0-9]/.test(password),
        special: /[^A-Za-z0-9]/.test(password),
    };

    setRequirement('req-length',  checks.length,  'At least 8 characters');
    setRequirement('req-upper',   checks.upper,   'One uppercase letter');
    setRequirement('req-lower',   checks.lower,   'One lowercase letter');
    setRequirement('req-number',  checks.number,  'One number');
    setRequirement('req-special', checks.special, 'One special character (e.g. ! @ # $)');

    const allPassed = Object.values(checks).every(Boolean);
    if (allPassed) {
        document.getElementById('password_requirements_error').style.display = 'none';
    }
    return allPassed;
}

function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const errorText = document.getElementById('password_match_error');

    if (confirmPassword.length > 0 && password !== confirmPassword) {
        errorText.style.display = 'block';
        return false;
    }
    errorText.style.display = 'none';
    return true;
}
function validateStaffRegistrationForm() {
    let valid = true;

    if (!checkPasswordStrength()) {
        document.getElementById('password_requirements_error').style.display = 'block';
        valid = false;
    }

    if (!checkPasswordMatch()) {
        valid = false;
    }

    const staffId = document.getElementById('staff_id').value;
    const staffIdError = document.getElementById('staff_id_error');
    if (!/^\d{6}$/.test(staffId)) {
        staffIdError.style.display = 'block';
        valid = false;
    } else {
        staffIdError.style.display = 'none';
    }

    const contactNo = document.getElementById('contact_no').value;
    const contactNoError = document.getElementById('contact_no_error');
    if (!/^09\d{2}-\d{3}-\d{4}$/.test(contactNo)) {
        contactNoError.style.display = 'block';
        valid = false;
    } else {
        contactNoError.style.display = 'none';
    }

    const nameFields = ['lname', 'fname', 'mname'];
    const letterPattern = /^[A-Za-z\s'-]*$/;
    for (const fieldId of nameFields) {
        const field = document.getElementById(fieldId);
        if (!letterPattern.test(field.value) || (field.required && field.value.trim() === '')) {
            valid = false;
        }
    }

    if (!valid) {
        return false;
    }

    // All fields passed validation — ask for confirmation before submitting.
    return confirm('Are you sure you want to register this staff account?');
}

document.addEventListener('DOMContentLoaded', function () {
    const contactInput = document.getElementById('contact_no');
    if (contactInput && contactInput.value === '') {
        contactInput.value = '09';
    }
});
</script>

</body>
</html>