<!-- Shared Navbar Partial -->
<nav class="main-navbar">
    <div class="nav-brand">
        <a href="home">
            <img src="assets/images/SOAU-image.png" alt="BSU ORG-TRACK logo" class="nav-logo">
            <span class="nav-brand-text">BSU ORG-TRACK</span>
        </a>
    </div>
    <ul class="nav-links">
        <li><a href="home" class="<?php echo basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : ''; ?>">Home</a></li>
        <li><a href="viewing" class="<?php echo basename($_SERVER['PHP_SELF']) == 'viewing.php' ? 'active' : ''; ?>">Viewing</a></li>
        <li><a href="signin" class="<?php echo basename($_SERVER['PHP_SELF']) == 'signin.php' ? 'active' : ''; ?>">Sign In</a></li>
    </ul>
</nav>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,700&display=swap');

    :root {
        --navbar-height: 80px;
    }

    .main-navbar {
        background: linear-gradient(135deg, #123a28 0%, #1f6d4c 100%);
        height: var(--navbar-height);
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 5%;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.14);
        font-family: 'Inter', system-ui, sans-serif;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        border-bottom: 3px solid #c9a227;
    }

    .nav-brand a {
        display: flex;
        align-items: center;
        gap: 14px;
        color: white;
        text-decoration: none;
        transition: opacity 0.2s;
    }

    .nav-brand a:hover {
        opacity: 0.9;
    }

    .nav-logo {
        height: 56px;
        width: auto;
        max-width: 220px;
        object-fit: contain;
        border-radius: 6px;
    }

    .nav-brand-text {
        font-family: 'Source Serif 4', Georgia, serif;
        font-weight: 700;
        font-size: 1.5rem;
        letter-spacing: -0.01em;
        white-space: nowrap;
    }

    .nav-links {
        list-style: none;
        display: flex;
        align-items: center;
        gap: 35px;
        margin: 0;
        padding: 0;
    }

    .nav-links li a {
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        font-size: 1.05rem;
        font-weight: 600;
        transition: all 0.2s;
        padding: 10px 0;
        position: relative;
    }

    .nav-links li a:hover {
        color: white;
    }

    .nav-links li a.active {
        color: white;
        font-weight: 800;
    }

    .nav-links li a.active::after {
        content: '';
        position: absolute;
        bottom: -4px;
        left: 0;
        right: 0;
        height: 3px;
        background: #c9a227;
        border-radius: 2px;
    }

    .signin-btn {
        background: white;
        color: #1f6d4c !important;
        padding: 10px 24px !important;
        border-radius: 8px;
        font-weight: 700 !important;
        transition: all 0.2s !important;
    }

    .signin-btn:hover {
        background: #f0f0f0;
        transform: translateY(-2px);
    }

    .signin-btn.active::after {
        display: none;
    }

    /* User info in navbar */
    .user-info-nav {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-left: 25px;
    }

    .user-greeting {
        color: #e9f5ef;
        font-size: 0.95rem;
        font-weight: 600;
    }

    /* Important: Adds space so content doesn't hide under the nav */
    .nav-spacer {
        height: var(--navbar-height);
        margin: 0;
        padding: 0;
        display: block;
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
        .main-navbar {
            padding: 15px 4%;
            height: auto;
            min-height: 70px;
            flex-direction: column;
        }

        .nav-brand {
            margin-bottom: 12px;
        }

        .nav-logo {
            height: 42px;
        }

        .nav-brand-text {
            font-size: 1.2rem;
        }

        .nav-links {
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .nav-links li a {
            font-size: 0.9rem;
            padding: 6px 0;
        }

        .nav-spacer {
            height: auto;
            min-height: 110px;
        }

        .user-info-nav {
            margin-left: 0;
        }
    }

    @media (max-width: 480px) {
        .nav-logo {
            height: 36px;
        }

        .nav-brand-text {
            font-size: 1.05rem;
        }

        .nav-links {
            gap: 15px;
        }

        .nav-links li a {
            font-size: 0.8rem;
        }

        .signin-btn {
            padding: 6px 16px !important;
        }
    }
</style>

<!-- Conditional spacer - adjusts based on if user is logged in -->
<div class="nav-spacer"></div>