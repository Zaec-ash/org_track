<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | BSU ORG-TRACK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="design/style.css" rel="stylesheet">

</head>
<body>
    <?php include "views/partials/nav.php"; ?>

    <section class="hero">
        <div class="hero-content">
            <h2>Start Your Journey with SOAU</h2>
            <p>Welcome to the official portal. To begin managing activities and filing permits, your organization must first register its account.</p>
            <a href="register" class="btn btn-primary">Register Your Organization</a>
        </div>
    </section>

    <section class="workflow">
        <h2>How It Works</h2>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3>Register Org</h3>
                <p>Create an account for your organization. This establishes your profile and SOAU status.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <h3>Verify Status</h3>
                <p>Once registered, wait for SOAU to approve your organization’s activation.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <h3>Register Activity</h3>
                <p>Logged-in organizations can now access the Activity Permit module to start filings.</p>
            </div>
        </div>
    </section>

    <section class="resources">
        <h2>Downloadable Templates</h2>
        <div class="resource-grid">
            <div class="resource-card">
                <h3>In-Campus Permit</h3>
                <p>Standardized form for within-university activities.</p>
                <a href="premade/RSO Activity Permit (In-Campus).pdf" download class="btn btn-primary">Download PDF</a>
            </div>
            <div class="resource-card">
                <h3>Out-Campus Permit</h3>
                <p>Standardized form for off-campus activities.</p>
                <a href="premade/RSO Activity Permit (Out-of-Campus).pdf" download class="btn btn-primary">Download PDF</a>
            </div>
            <div class="resource-card">
                <h3>Accomplishment Report</h3>
                <p>Post-activity evaluation form.</p>
                <a href="premade/RSO Activity Accomplishment Report.docx" download class="btn btn-primary">Download DOCX</a>
            </div>
            <div class="resource-card">
                <h3>Activity Permit Design</h3>
                <p>Design Template for Activity Permit.</p>
                <a href="premade/RSO Activity Design Template.docx" download class="btn btn-primary">Download DOCX</a>
            </div>
            <div class="resource-card">
                <h3>Out-Campus Risk Assessment Form</h3>
                <p>Risk Assessment form for off-campus activities.</p>
                <a href="premade/RSO Risk Assessment Form (Out-of-Campus).docx" download class="btn btn-primary">Download DOCX</a>
            </div>
            <div class="resource-card">
                <h3>In-Campus Risk Assessment Form</h3>
                <p>Risk Assessment form for in-campus activities.</p>
                <a href="premade/RSO Risk Assessment Form (In Campus).docx" download class="btn btn-primary">Download DOCX</a>
            </div>
        </div>
    </section>

  <footer class="site-footer">
    <div class="footer-main">
        <div class="footer-brand">
            <h2>BSU ORG-TRACK</h2>
            <p>Recognized Student Organization Management &amp; Activity Permit System</p>
        </div>

        <div class="footer-col">
            <span class="footer-col-title">Office</span>
            <span class="footer-text">Student Organizations &amp; Activities Unit</span>
            <span class="footer-text">Benguet State University</span>
            <span class="footer-text">La Trinidad, Benguet</span>
        </div>
    </div>

    <div class="footer-bottom">
        <span>&copy; 2026 Benguet State University — ORG-TRACK</span>
    </div>
</footer>

<style>
.site-footer {
    background: linear-gradient(180deg, #1b4332 0%, #143527 100%);
    color: #e8f0ec;
    padding: 24px 40px 0;
    font-family: inherit;
}

.footer-main {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    max-width: 1100px;
    margin: 0 auto;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(232, 240, 236, 0.15);
}

.footer-brand h2 {
    margin: 0 0 4px 0;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: 0.3px;
    color: #ffffff;
}

.footer-brand p {
    margin: 0;
    max-width: 260px;
    font-size: 0.75rem;
    line-height: 1.4;
    color: rgba(232, 240, 236, 0.75);
}

.footer-col {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.footer-col-title {
    font-size: 0.65rem;
    font-weight: 700;
    color: #95d5b2;
    margin-bottom: 2px;
}

.footer-text {
    font-size: 0.75rem;
    color: rgba(232, 240, 236, 0.85);
}

.footer-bottom {
    max-width: 1100px;
    margin: 0 auto;
    padding: 12px 0;
    font-size: 0.7rem;
    color: rgba(232, 240, 236, 0.6);
    text-align: center;
}

@media (max-width: 640px) {
    .site-footer {
        padding: 20px 20px 0;
    }
    .footer-main {
        flex-direction: column;
        gap: 16px;
    }
}
</style>
</body>
</html>