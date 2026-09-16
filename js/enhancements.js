/* ==========================================================================
   BSU ORG-TRACK — ENHANCEMENT SCRIPTS
   Include once, near the end of <body>, after enhancements.css.
   Every function checks for its markup before running, so this is safe
   to drop on any page even if a given component isn't present.
   ==========================================================================
   1. Scroll-Shrink Navbar
   2. Animated Stat Counters
   3. Radial Rating Gauges
   4. Mini Sparklines
   5. Filter Chips
   6. Announcement Banner Dismiss
   7. File Upload Dropzone
   8. Confetti Burst
   9. Dark Mode Toggle
   ========================================================================== */

document.addEventListener('DOMContentLoaded', function () {
    initScrollNavbar();
    initStatCounters();
    initRatingRings();
    initSparklines();
    initFilterChips();
    initAnnouncementBanner();
    initUploadDropzone();
    initDarkModeToggle();
});

/* -------------------------------------------------------------------- */
/* 1. Scroll-Shrink Navbar                                               */
/* -------------------------------------------------------------------- */
function initScrollNavbar() {
    var nav = document.querySelector('.main-navbar');
    if (!nav) return;

    var threshold = 40;
    window.addEventListener('scroll', function () {
        if (window.scrollY > threshold) {
            nav.classList.add('navbar-scrolled');
        } else {
            nav.classList.remove('navbar-scrolled');
        }
    }, { passive: true });
}

/* -------------------------------------------------------------------- */
/* 2. Animated Stat Counters                                             */
/* Usage: <div class="stat-value" data-target="128">0</div>             */
/* -------------------------------------------------------------------- */
function initStatCounters() {
    var counters = document.querySelectorAll('.stat-value[data-target]');
    if (!counters.length) return;

    var animate = function (el) {
        var target = parseFloat(el.getAttribute('data-target')) || 0;
        var duration = 900;
        var start = null;
        el.classList.add('counting');

        function step(timestamp) {
            if (!start) start = timestamp;
            var progress = Math.min((timestamp - start) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            var value = Math.round(eased * target);
            el.textContent = value;
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                el.textContent = target;
                el.classList.remove('counting');
                el.classList.add('count-done');
            }
        }
        requestAnimationFrame(step);
    };

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4 });

        counters.forEach(function (el) { observer.observe(el); });
    } else {
        counters.forEach(animate);
    }
}

/* -------------------------------------------------------------------- */
/* 3. Radial Rating Gauges                                               */
/* Usage: <div class="rating-ring" data-rating="79"></div>              */
/* -------------------------------------------------------------------- */
function initRatingRings() {
    var rings = document.querySelectorAll('.rating-ring[data-rating]');
    if (!rings.length) return;

    rings.forEach(function (ring) {
        var pct = Math.max(0, Math.min(100, parseFloat(ring.getAttribute('data-rating')) || 0));

        if (!ring.querySelector('.rating-ring-value')) {
            var label = document.createElement('span');
            label.className = 'rating-ring-value';
            label.textContent = pct + '%';
            ring.appendChild(label);
        }

        if (pct < 50) ring.classList.add('low');
        else if (pct < 80) ring.classList.add('mid');
        else ring.classList.add('high');

        // Animate from 0 to target percent
        var current = 0;
        ring.style.setProperty('--pct', 0);
        var interval = setInterval(function () {
            current += 2;
            if (current >= pct) {
                current = pct;
                clearInterval(interval);
            }
            ring.style.setProperty('--pct', current);
        }, 12);
    });
}

/* -------------------------------------------------------------------- */
/* 4. Mini Sparklines                                                    */
/* Usage: <div class="sparkline" data-points="4,8,6,10,14,9,16"></div>  */
/* -------------------------------------------------------------------- */
function initSparklines() {
    var sparks = document.querySelectorAll('.sparkline[data-points]');
    if (!sparks.length) return;

    sparks.forEach(function (el) {
        var points = el.getAttribute('data-points').split(',').map(Number);
        if (!points.length) return;

        var w = 90, h = 28, pad = 3;
        var max = Math.max.apply(null, points);
        var min = Math.min.apply(null, points);
        var range = max - min || 1;

        var coords = points.map(function (val, i) {
            var x = pad + (i / (points.length - 1)) * (w - pad * 2);
            var y = h - pad - ((val - min) / range) * (h - pad * 2);
            return x.toFixed(1) + ',' + y.toFixed(1);
        });

        var lastCoord = coords[coords.length - 1].split(',');

        var svg = '<svg viewBox="0 0 ' + w + ' ' + h + '" preserveAspectRatio="none">' +
            '<polyline points="' + coords.join(' ') + '"></polyline>' +
            '<circle cx="' + lastCoord[0] + '" cy="' + lastCoord[1] + '" r="2.5"></circle>' +
            '</svg>';

        el.innerHTML = svg;
    });
}

/* -------------------------------------------------------------------- */
/* 5. Filter Chips                                                       */
/* Usage: <button class="chip" data-filter="approved">Approved</button> */
/* Matches against elements with data-status or data-type attributes.   */
/* -------------------------------------------------------------------- */
function initFilterChips() {
    var containers = document.querySelectorAll('.filter-chips');
    if (!containers.length) return;

    containers.forEach(function (container) {
        var chips = container.querySelectorAll('.chip');
        var table = container.closest('.card-body, .container, body').querySelector('tbody');

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                chips.forEach(function (c) { c.classList.remove('active'); });
                chip.classList.add('active');

                var filter = chip.getAttribute('data-filter');
                if (!table) return;

                var rows = table.querySelectorAll('tr');
                rows.forEach(function (row) {
                    if (filter === 'all' || !filter) {
                        row.style.display = '';
                        return;
                    }
                    var status = (row.getAttribute('data-status') || '').toLowerCase();
                    var type = (row.getAttribute('data-type') || '').toLowerCase();
                    var match = status === filter.toLowerCase() || type === filter.toLowerCase();
                    row.style.display = match ? '' : 'none';
                });
            });
        });
    });
}

/* -------------------------------------------------------------------- */
/* 6. Announcement Banner Dismiss (remembers dismissal per banner id)   */
/* -------------------------------------------------------------------- */
function initAnnouncementBanner() {
    var banners = document.querySelectorAll('.announcement-banner');
    if (!banners.length) return;

    banners.forEach(function (banner) {
        var key = 'dismissed-' + (banner.id || 'announcement');

        if (localStorage.getItem(key) === '1') {
            banner.style.display = 'none';
            return;
        }

        var dismissBtn = banner.querySelector('.announcement-dismiss');
        if (!dismissBtn) return;

        dismissBtn.addEventListener('click', function () {
            banner.classList.add('dismissed');
            localStorage.setItem(key, '1');
            setTimeout(function () { banner.style.display = 'none'; }, 300);
        });
    });
}

/* -------------------------------------------------------------------- */
/* 7. File Upload Dropzone                                               */
/* Usage: see enhancements.css section 11 for required markup.          */
/* -------------------------------------------------------------------- */
function initUploadDropzone() {
    var zone = document.getElementById('uploadDropzone');
    if (!zone) return;

    var input = zone.querySelector('input[type="file"]');
    var list = zone.querySelector('.upload-file-list') || document.getElementById('uploadFileList');
    var selectedFiles = [];

    var renderList = function () {
        if (!list) return;
        list.innerHTML = '';
        selectedFiles.forEach(function (file, index) {
            var chip = document.createElement('span');
            chip.className = 'upload-file-chip';
            chip.innerHTML = '<i class="fas fa-file"></i> ' +
                escapeHtml(file.name) +
                ' <button type="button" class="upload-file-remove" data-index="' + index + '">&times;</button>';
            list.appendChild(chip);
        });

        list.querySelectorAll('.upload-file-remove').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                selectedFiles.splice(parseInt(btn.getAttribute('data-index'), 10), 1);
                renderList();
            });
        });
    };

    var addFiles = function (fileList) {
        Array.prototype.forEach.call(fileList, function (file) {
            selectedFiles.push(file);
        });
        renderList();
    };

    zone.addEventListener('click', function () {
        if (input) input.click();
    });

    if (input) {
        input.addEventListener('change', function () {
            addFiles(input.files);
        });
    }

    ['dragenter', 'dragover'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) {
            e.preventDefault();
            zone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) {
            e.preventDefault();
            zone.classList.remove('dragover');
        });
    });

    zone.addEventListener('drop', function (e) {
        if (e.dataTransfer && e.dataTransfer.files) {
            addFiles(e.dataTransfer.files);
        }
    });
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

/* -------------------------------------------------------------------- */
/* 8. Confetti Burst                                                     */
/* Usage: call window.fireConfetti() from any click handler, e.g.       */
/* <button onclick="fireConfetti()">Approve</button>                    */
/* Requires <canvas id="confettiCanvas"></canvas> somewhere in <body>.  */
/* -------------------------------------------------------------------- */
window.fireConfetti = function (originEl) {
    var canvas = document.getElementById('confettiCanvas');
    if (!canvas) return;

    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    var ctx = canvas.getContext('2d');

    var originX = window.innerWidth / 2;
    var originY = window.innerHeight / 3;

    if (originEl && originEl.getBoundingClientRect) {
        var rect = originEl.getBoundingClientRect();
        originX = rect.left + rect.width / 2;
        originY = rect.top + rect.height / 2;
    }

    var colors = ['#1f6d4c', '#c9a227', '#2f6fa8', '#e9f5ef', '#ffffff'];
    var particles = [];
    var count = 90;

    for (var i = 0; i < count; i++) {
        var angle = Math.random() * Math.PI * 2;
        var speed = 3 + Math.random() * 6;
        particles.push({
            x: originX,
            y: originY,
            vx: Math.cos(angle) * speed,
            vy: Math.sin(angle) * speed - 3,
            size: 4 + Math.random() * 4,
            color: colors[Math.floor(Math.random() * colors.length)],
            rotation: Math.random() * 360,
            rotationSpeed: (Math.random() - 0.5) * 12,
            life: 60 + Math.random() * 30
        });
    }

    var frame = 0;
    function tick() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        frame++;
        var alive = false;

        particles.forEach(function (p) {
            if (p.life <= 0) return;
            alive = true;
            p.vy += 0.15; // gravity
            p.x += p.vx;
            p.y += p.vy;
            p.rotation += p.rotationSpeed;
            p.life--;

            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate((p.rotation * Math.PI) / 180);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
            ctx.restore();
        });

        if (alive && frame < 150) {
            requestAnimationFrame(tick);
        } else {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    }
    requestAnimationFrame(tick);
};

/* -------------------------------------------------------------------- */
/* 9. Dark Mode Toggle                                                   */
/* Usage: <button class="theme-toggle" id="themeToggle"></button>       */
/* -------------------------------------------------------------------- */
function initDarkModeToggle() {
    var toggle = document.getElementById('themeToggle');
    var stored = localStorage.getItem('bsu-theme');

    if (stored === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }

    if (!toggle) return;

    toggle.addEventListener('click', function () {
        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (isDark) {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('bsu-theme', 'light');
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('bsu-theme', 'dark');
        }
    });
}

/* -------------------------------------------------------------------- */
/* 10. Toast Notifications                                               */
/* Replaces native browser alert() popups.                              */
/* Usage: <div id="toastContainer" class="toast-container"></div>      */
/* Call: showToast('Saved successfully!', 'success');                   */
/* Types: 'success' (default), 'error', 'info'                          */
/* -------------------------------------------------------------------- */
window.showToast = function (message, type) {
    type = type || 'success';
    var container = document.getElementById('toastContainer');
    if (!container) return;

    var icons = { success: 'check-circle', error: 'exclamation-triangle', info: 'info-circle' };
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML = '<i class="fas fa-' + (icons[type] || 'info-circle') + '"></i><span>' + message + '</span>';
    container.appendChild(toast);
    setTimeout(function () { toast.remove(); }, 4300); // matches the CSS animation timing
};

/* -------------------------------------------------------------------- */
/* 11. Custom Confirm Modal                                              */
/* Replaces native browser confirm() dialogs.                           */
/* Usage:                                                                */
/* <div id="customConfirmModal" class="modal">                          */
/*     <div class="modal-content confirm-modal-content">                */
/*         <div class="confirm-icon"><i class="fas fa-question-circle"></i></div> */
/*         <p class="confirm-message" id="confirmMessage"></p>          */
/*         <div class="confirm-actions">                                */
/*             <button type="button" class="btn-sm btn-outline" id="confirmCancelBtn">Cancel</button> */
/*             <button type="button" class="btn-sm btn-approve" id="confirmOkBtn">Yes, Continue</button> */
/*         </div>                                                       */
/*     </div>                                                           */
/* </div>                                                                */
/* Call: showConfirm('Delete this item?', function () { ... }, {okClass: 'btn-reject', okText: 'Delete'}); */
/* -------------------------------------------------------------------- */
window.showConfirm = function (message, onConfirm, opts) {
    opts = opts || {};
    var modal = document.getElementById('customConfirmModal');
    if (!modal) return;

    var msgEl = document.getElementById('confirmMessage');
    var okBtn = document.getElementById('confirmOkBtn');
    var cancelBtn = document.getElementById('confirmCancelBtn');

    msgEl.textContent = message;
    okBtn.textContent = opts.okText || 'Yes, Continue';
    okBtn.className = 'btn-sm ' + (opts.okClass || 'btn-approve');

    modal.classList.add('active');

    function cleanup() {
        modal.classList.remove('active');
        okBtn.removeEventListener('click', handleOk);
        cancelBtn.removeEventListener('click', handleCancel);
    }
    function handleOk() { cleanup(); if (typeof onConfirm === 'function') onConfirm(); }
    function handleCancel() { cleanup(); }

    okBtn.addEventListener('click', handleOk);
    cancelBtn.addEventListener('click', handleCancel);
};

// Use on a form's onsubmit (or a submit button's onclick) in place of
// onsubmit="return confirm('...')". Prevents the native submit, shows the
// custom modal, and submits the form for real only if the user confirms.
window.handleFormConfirm = function (event, message, opts) {
    event.preventDefault();
    var target = event.target;
    var form = target.form || target.closest('form') || target;

    // event.currentTarget is always the element the onclick handler is
    // bound to (the button itself). event.target can instead be an inner
    // element — e.g. the <i> icon inside the button — if that's the exact
    // pixel the user clicked, which would otherwise make this silently
    // fail to find the button's name/value.
    var button = event.currentTarget;
    var btnName = (button && (button.tagName === 'BUTTON' || button.tagName === 'INPUT')) ? button.name : null;
    var btnValue = btnName ? (button.value || '1') : null;

    window.showConfirm(message, function () {
        if (btnName && !form.querySelector('input[name="' + btnName + '"]')) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = btnName;
            hidden.value = btnValue;
            form.appendChild(hidden);
        }
        form.submit();
    }, opts);
    return false;
};