/* =============================================
   EVSU RESERVE — admin_users.js
   AJAX role update, live search,
   toast notifications, sidebar toggle
   ============================================= */

'use strict';

/* ══════════════════════════════════════════════
   SIDEBAR TOGGLE
   ══════════════════════════════════════════════ */
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  const isOpen  = sidebar.classList.contains('open');
  sidebar.classList.toggle('open', !isOpen);
  overlay.classList.toggle('open', !isOpen);
  document.body.style.overflow = isOpen ? '' : 'hidden';
}
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    const sidebar = document.getElementById('sidebar');
    if (sidebar.classList.contains('open')) toggleSidebar();
  }
});

/* ══════════════════════════════════════════════
   TOAST
   ══════════════════════════════════════════════ */
let toastTimer = null;
function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = message;
  toast.className   = `toast ${type} show`;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 3200);
}

/* ══════════════════════════════════════════════
   ROLE BADGE CLASSES
   Mirrors PHP $role_badge map
   ══════════════════════════════════════════════ */
const ROLE_CLASS = {
  admin:   'badge-role-admin',
  staff:   'badge-role-staff',
  cashier: 'badge-role-cashier',
  student: 'badge-role-student',
};

/* ══════════════════════════════════════════════
   UPDATE ROLE — AJAX POST
   Called by onchange on each role <select>
   ══════════════════════════════════════════════ */
function updateRole(selectEl) {
  const userId      = selectEl.dataset.userId;
  const newRole     = selectEl.value;
  const prevRole    = selectEl.dataset.currentRole;

  if (newRole === prevRole) return;

  // Confirm for sensitive promotions
  if (newRole === 'admin') {
    if (!confirm(`Promote this user to Admin? This grants full system access.`)) {
      selectEl.value = prevRole;
      return;
    }
  }

  // Disable while saving
  selectEl.classList.add('saving');

  const formData = new FormData();
  formData.append('action',  'update_role');
  formData.append('user_id', userId);
  formData.append('role',    newRole);

  fetch(window.location.pathname, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        selectEl.dataset.currentRole = newRole;
        updateRowBadge(selectEl.closest('tr'), newRole);
        showToast(`User role updated to ${capitalize(newRole)}`, 'success');
      } else {
        selectEl.value = prevRole;
        showToast('Failed to update role. Please try again.', 'error');
      }
    })
    .catch(() => {
      selectEl.value = prevRole;
      showToast('Network error. Please try again.', 'error');
    })
    .finally(() => selectEl.classList.remove('saving'));
}

/* Update the role badge + avatar colour in the same row */
function updateRowBadge(row, newRole) {
  if (!row) return;
  const newCls = ROLE_CLASS[newRole] || 'badge-role-student';

  // Role badge pill
  const badge = row.querySelector('.role-badge');
  if (badge) {
    badge.className = `role-badge ${newCls}`;
    badge.textContent = capitalize(newRole);
  }

  // Avatar initial circle
  const avatar = row.querySelector('.user-avatar-sm');
  if (avatar) {
    Object.values(ROLE_CLASS).forEach(c => avatar.classList.remove(c));
    avatar.classList.add(newCls);
  }
}

function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

/* ══════════════════════════════════════════════
   LIVE CLIENT-SIDE SEARCH
   Filters rows instantly as you type;
   also updates the user count pill.
   ══════════════════════════════════════════════ */
(function initLiveSearch() {
  const input     = document.getElementById('searchInput');
  const countPill = document.querySelector('.user-count-pill');
  if (!input) return;

  let timer;
  input.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(() => {
      const q    = this.value.toLowerCase();
      const rows = document.querySelectorAll('.orders-table tbody tr');
      let visible = 0;

      rows.forEach(row => {
        const text    = row.textContent.toLowerCase();
        const matches = !q || text.includes(q);
        row.style.display = matches ? '' : 'none';
        if (matches) visible++;
      });

      if (countPill) {
        countPill.textContent = visible + ' user' + (visible !== 1 ? 's' : '');
      }
    }, 180);
  });

  /* Submit on Enter for server-side fallback */
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') document.getElementById('filterForm')?.submit();
  });
})();
