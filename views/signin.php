<?php 
include "views/partials/nav.php"; 

// Grab session messages if they exist
$error_message = $_SESSION['error'] ?? null;
$success_message = $_SESSION['success'] ?? null;
$redirect_url = $_SESSION['redirect_url'] ?? null;

// Clear session alerts after loading
unset($_SESSION['error'], $_SESSION['success'], $_SESSION['redirect_url']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | SOAU BSU Portal</title>
    <link href="design/signin_style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800" rel="stylesheet">
    
    <style>
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #34d399;
        }
    </style>

    <?php if ($success_message && $redirect_url): ?>
        <!-- Auto-redirect to dashboard after 1.5 seconds -->
        <meta http-equiv="refresh" content="1.5;url=<?php echo $redirect_url; ?>">
    <?php endif; ?>
</head>
<body>

<div class="main-content">
    <div class="login-container">
        <div class="portal-card">
            <header class="portal-header">
                <h1>BSU ORG-TRACK</h1>
                <p>Student Organizations and Affairs Unit</p>
            </header>

            <div class="card-body">
                <!-- Success Notification -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <span>✅</span> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Error Notification -->
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <span>⚠️</span> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form action="LogInUser" method="POST" id="loginForm">
                    <div class="form-section">
                        <span class="section-label">Sign In to Your Account</span>
                        
                        <div class="field-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" id="email" placeholder="your@email.com" required>
                            <small id="emailHint" style="font-size: 0.7rem; color: var(--text-muted);"></small>
                        </div>
                        
                        <div class="field-group">
                            <label>Password <span class="required">*</span></label>
                            <input type="password" name="password" placeholder="Enter password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Sign In →
                    </button>
                </form>

                <div class="info-note">
                    Staff/Admin require @bsu.edu.ph email.
                </div>
            </div>

            <div class="portal-footer">
                <div class="footer-text">
                    New to BSU ORG-Track? <a href="register">Register Organization</a>
                </div>
                <div class="register-links">
                    <div class="register-link"><a href="register_staff">Register as SOAU Staff</a></div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>