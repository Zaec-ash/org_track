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

    function handleLogout() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout';
        }
    }
    
    function filterActivities() {
        const input = document.getElementById("activitySearch");
        if (!input) return;
        const filter = input.value.toLowerCase();
        const table = document.getElementById("activityTable");
        if (!table) return;
        const tr = table.getElementsByTagName("tr");
        
        for (let i = 1; i < tr.length; i++) {
            const td = tr[i].getElementsByTagName("td");
            let found = false;
            for (let j = 0; j < td.length; j++) {
                if (td[j]) {
                    const textValue = td[j].textContent || td[j].innerText;
                    if (textValue.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            tr[i].style.display = found ? "" : "none";
        }
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
        alert(`Viewing application #${appId}\n\nIn production, this would show full details.`);
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
    
    function openAccomplishmentModal(permitId, activityTitle) {
        document.getElementById('modal_permit_id').value = permitId;
        document.getElementById('modal_activity_title_accomplishment').value = activityTitle;
        document.getElementById('modal_permit_id_display').innerText = permitId;
        document.getElementById('modal_activity_title_display').innerText = activityTitle;
        document.getElementById('accomplishmentModal').classList.add('active');
    }
    
    function closeAccomplishmentModal() {
        document.getElementById('accomplishmentModal').classList.remove('active');
        document.getElementById('accomplishmentForm').reset();
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
        if (event.target === reqModal) closeRequirementsModal();
        if (event.target === accModal) closeAccomplishmentModal();
                            }
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i><span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 4300); // matches the CSS animation timing
    }

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