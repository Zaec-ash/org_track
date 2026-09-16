<!-- Updated Navbar - Larger Design -->
<nav class="main-navbar">
    <div style="display: flex; align-items: center; gap: 12px;">
        <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="nav-brand">
            <a href="home">
                <img src="assets/images/SOAU-image.png" alt="BSU ORG-TRACK logo" class="nav-logo">
                <span class="nav-brand-text">BSU ORG-TRACK</span>
            </a>
        </div>
    </div>

    <div style="display: flex; align-items: center; gap: 12px;">
        <div class="nav-profile-dropdown" id="navProfileDropdown">
            <button class="nav-profile-btn" onclick="toggleProfileDropdown(event)" aria-haspopup="true" aria-expanded="false" id="navProfileBtn">
                <i class="fas fa-user-circle"></i>
                <span class="nav-profile-label">Profile</span>
                <i class="fas fa-chevron-down nav-profile-caret"></i>
            </button>
            <div class="nav-profile-menu" id="navProfileMenu">
                <button class="nav-profile-menu-item" onclick="if (typeof switchTab === 'function') switchTab('profile'); closeProfileDropdown();">
                    <i class="fas fa-user"></i> My Profile
                </button>
                <button class="nav-profile-menu-item" onclick="if (typeof switchTab === 'function') switchTab('password'); closeProfileDropdown();">
                    <i class="fas fa-lock"></i> Security
                </button>
                <div class="nav-profile-menu-divider"></div>
                <button class="nav-profile-menu-item nav-profile-menu-logout" onclick="closeProfileDropdown(); if (typeof handleLogout === 'function') handleLogout();">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
        <div class="notif-dropdown" id="notifDropdown">
            <button class="notif-bell" aria-label="Notifications" aria-haspopup="true" aria-expanded="false" id="notifBellBtn" onclick="toggleNotifDropdown(event)">
                <i class="fas fa-bell"></i>
                <span class="notif-badge" id="navNotifBadge" style="display: none;">0</span>
            </button>
            <div class="notif-menu" id="notifMenu">
                <div class="notif-menu-header">Notifications</div>
                <div class="notif-menu-body" id="notifMenuBody">
                    <div class="notif-empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <p>No new notifications</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>

    :root {
        --navbar-height: 70px;
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
        gap: 12px;
        color: white;
        text-decoration: none;
    }

    .nav-brand-text {
        font-weight: 800;
        font-family: 'Source Serif 4', Georgia, serif;
        font-size: 1.5rem;
        letter-spacing: -0.01em;
        white-space: nowrap;
    }

    .nav-logo {
        height: 54px;
        width: auto;
        max-width: 220px;
        object-fit: contain;
        border-radius: 6px;
    }

    .sidebar-toggle {
        display: none;
        align-items: center;
        justify-content: center;
        height: 40px;
        border-radius: 12px;
        border: none;
        background: #1f6d4c;
        color: white;
        font-size: 1rem;
        box-shadow: 0 1px 2px rgba(18, 40, 30, 0.06);
        cursor: pointer;
        padding: 0 16px;
        z-index: 1100;
    }

    .sidebar-toggle:hover { background: #123a28; }

    @media (max-width: 900px) {
        .sidebar-toggle { display: flex; }
    }

    .nav-profile-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.12);
        border: none;
        color: white;
        padding: 8px 16px;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.18s ease, transform 0.18s ease;
    }

    .nav-profile-btn:hover {
        background: rgba(255, 255, 255, 0.22);
        transform: translateY(-1px);
    }

    .nav-profile-btn i {
        font-size: 1.1rem;
    }

    .nav-profile-caret {
        font-size: 0.7rem !important;
        margin-left: 2px;
        transition: transform 0.2s ease;
    }

    .nav-profile-dropdown {
        position: relative;
    }

    .nav-profile-dropdown.open .nav-profile-caret {
        transform: rotate(180deg);
    }

    .nav-profile-dropdown.open .nav-profile-btn {
        background: rgba(255, 255, 255, 0.24);
    }

    .nav-profile-menu {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        min-width: 190px;
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.18);
        padding: 8px;
        display: none;
        flex-direction: column;
        gap: 2px;
        z-index: 1200;
        animation: navProfileMenuIn 0.16s ease both;
    }

    .nav-profile-dropdown.open .nav-profile-menu {
        display: flex;
    }

    @keyframes navProfileMenuIn {
        from { opacity: 0; transform: translateY(-6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .nav-profile-menu-item {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        text-align: left;
        background: none;
        border: none;
        padding: 10px 12px;
        border-radius: 9px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #16201b;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }

    .nav-profile-menu-item:hover {
        background: #e9f5ef;
    }

    .nav-profile-menu-item i {
        width: 16px;
        color: #1f6d4c;
    }

    .nav-profile-menu-divider {
        height: 1px;
        background: #eceeec;
        margin: 4px 2px;
    }

    .nav-profile-menu-logout {
        color: #c0392b;
    }

    .nav-profile-menu-logout i {
        color: #c0392b;
    }

    .nav-profile-menu-logout:hover {
        background: #fdecea;
    }

    .notif-dropdown {
        position: relative;
    }

    .notif-bell {
        position: relative;
        background: rgba(255, 255, 255, 0.12);
        border: none;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        color: white;
        font-size: 1.05rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.18s ease, transform 0.18s ease;
    }

    .notif-bell:hover {
        background: rgba(255, 255, 255, 0.22);
        transform: translateY(-1px);
    }

    .notif-dropdown.open .notif-bell {
        background: rgba(255, 255, 255, 0.24);
    }

    .notif-menu {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        width: 280px;
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.18);
        display: none;
        flex-direction: column;
        z-index: 1200;
        overflow: hidden;
        animation: navProfileMenuIn 0.16s ease both;
    }

    .notif-dropdown.open .notif-menu {
        display: flex;
    }

    .notif-menu-header {
        padding: 14px 16px;
        font-weight: 700;
        font-size: 0.85rem;
        color: #16201b;
        border-bottom: 1px solid #eceeec;
    }

    .notif-menu-body {
        max-height: 320px;
        overflow-y: auto;
    }

    .notif-empty-state {
        padding: 32px 20px;
        text-align: center;
        color: #8a938e;
    }

    .notif-empty-state i {
        font-size: 1.8rem;
        margin-bottom: 10px;
        display: block;
        opacity: 0.6;
    }

    .notif-empty-state p {
        margin: 0;
        font-size: 0.85rem;
    }

    .notif-item {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 12px 16px;
        border-bottom: 1px solid #f2f4f2;
        text-align: left;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }

    .notif-item:last-child {
        border-bottom: none;
    }

    .notif-item:hover {
        background: #e9f5ef;
    }

    .notif-item i {
        color: #1f6d4c;
        margin-top: 2px;
    }

    .notif-item-text {
        font-size: 0.8rem;
        color: #16201b;
        line-height: 1.4;
    }

    .notif-item-time {
        font-size: 0.7rem;
        color: #8a938e;
        margin-top: 2px;
        display: block;
    }

    @media (max-width: 480px) {
        .notif-menu {
            width: 240px;
            right: -50px;
        }
    }

    .notif-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: #c9a227;
        color: #123a28;
        font-size: 0.65rem;
        font-weight: 800;
        min-width: 18px;
        height: 18px;
        padding: 0 4px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #123a28;
        animation: notifPulse 2s ease-in-out infinite;
    }

    @keyframes notifPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(201, 162, 39, 0.55); }
        50%      { box-shadow: 0 0 0 6px rgba(201, 162, 39, 0); }
    }

    .nav-spacer {
        height: var(--navbar-height);
        margin: 0;
        padding: 0;
        display: block;
    }

    @media (max-width: 640px) {
        .nav-profile-label {
            display: none;
        }
        .nav-profile-btn {
            padding: 8px 10px;
        }
        .nav-profile-menu {
            right: -10px;
        }
    }
</style>
<!-- Conditional spacer - adjusts based on if user is logged in -->
<div class="nav-spacer"></div>
 <script>
    // Force global window attachment so inline onclick can find it immediately
    window.toggleSidebar = function() {
        var tabs = document.getElementById('verticalTabs');
        var overlay = document.getElementById('sidebarOverlay');
        
        if (tabs) tabs.classList.toggle('open');
        if (overlay) overlay.classList.toggle('open');
    };

    window.closeSidebar = function() {
        var tabs = document.getElementById('verticalTabs');
        var overlay = document.getElementById('sidebarOverlay');
        
        if (tabs) tabs.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
    };

    // Profile dropdown
    window.toggleProfileDropdown = function(e) {
        if (e) e.stopPropagation();
        var dropdown = document.getElementById('navProfileDropdown');
        var btn = document.getElementById('navProfileBtn');
        if (!dropdown) return;

        // Close the notification dropdown first, if open, so only one is open at a time
        if (typeof window.closeNotifDropdown === 'function') window.closeNotifDropdown();

        var isOpen = dropdown.classList.toggle('open');
        if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };

    window.closeProfileDropdown = function() {
        var dropdown = document.getElementById('navProfileDropdown');
        var btn = document.getElementById('navProfileBtn');
        if (dropdown) dropdown.classList.remove('open');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    };

    // Notification dropdown
    window.toggleNotifDropdown = function (e) {
        if (e) e.stopPropagation();
        var dropdown = document.getElementById('notifDropdown');
        var btn = document.getElementById('notifBellBtn');
        if (!dropdown) return;

        // Close the profile dropdown first, if open, so only one is open at a time
        window.closeProfileDropdown();

        var isOpen = dropdown.classList.toggle('open');
        if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };

    window.closeNotifDropdown = function () {
        var dropdown = document.getElementById('notifDropdown');
        var btn = document.getElementById('notifBellBtn');
        if (dropdown) dropdown.classList.remove('open');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    };

    // Close the dropdown when clicking anywhere else on the page
    document.addEventListener('click', function (e) {
        var dropdown = document.getElementById('navProfileDropdown');
        if (dropdown && dropdown.classList.contains('open') && !dropdown.contains(e.target)) {
            window.closeProfileDropdown();
        }

        var notifDropdown = document.getElementById('notifDropdown');
        if (notifDropdown && notifDropdown.classList.contains('open') && !notifDropdown.contains(e.target)) {
            window.closeNotifDropdown();
        }
    });

    // Close the dropdown on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            window.closeProfileDropdown();
            window.closeNotifDropdown();
        }
    });
    
</script>