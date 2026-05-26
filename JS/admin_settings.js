/* =============================================
   EVSU RESERVE — admin_settings.js
   Dirty-state tracking, password toggle,
   save button feedback, sidebar toggle
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
   DIRTY-STATE TRACKING
   Mirrors React's formValues — tracks which
   fields have changed from their original value,
   then enables the Save button and shows the
   "unsaved changes" hint only when needed.
   ══════════════════════════════════════════════ */
(function initDirtyTracking() {
  const form      = document.getElementById('settingsForm');
  const saveBtn   = document.getElementById('saveBtn');
  const saveHint  = document.getElementById('saveHint');
  if (!form || !saveBtn) return;

  // Capture original values on page load
  const originals = {};
  form.querySelectorAll('.field-input, .field-textarea').forEach(el => {
    originals[el.name] = el.value;
  });

  function checkDirty() {
    let dirty = false;
    form.querySelectorAll('.field-input, .field-textarea').forEach(el => {
      const changed = el.value !== originals[el.name];
      el.classList.toggle('is-dirty', changed);
      if (changed) dirty = true;
    });

    saveBtn.disabled = !dirty;
    if (saveHint) saveHint.style.display = dirty ? '' : 'none';
  }

  // Disable button on initial load (nothing changed yet)
  saveBtn.disabled = true;

  form.querySelectorAll('.field-input, .field-textarea').forEach(el => {
    el.addEventListener('input', checkDirty);
  });

  /* Show loading state on submit */
  form.addEventListener('submit', () => {
    saveBtn.disabled = true;
    saveBtn.classList.add('loading');
    const label = document.getElementById('saveBtnLabel');
    if (label) label.textContent = 'Saving…';
  });
})();

/* ══════════════════════════════════════════════
   PASSWORD / API KEY VISIBILITY TOGGLE
   ══════════════════════════════════════════════ */
function toggleVisibility(fieldId, btn) {
  const input   = document.getElementById(fieldId);
  const showEye = document.getElementById('eye-show-' + fieldId);
  const hideEye = document.getElementById('eye-hide-' + fieldId);
  if (!input) return;

  const isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';

  if (showEye) showEye.style.display = isHidden ? 'none' : '';
  if (hideEye) hideEye.style.display = isHidden ? ''     : 'none';

  btn.title = isHidden ? 'Hide key' : 'Show key';
}

/* ══════════════════════════════════════════════
   AUTO-DISMISS SUCCESS / ERROR ALERT
   ══════════════════════════════════════════════ */
(function initAlertDismiss() {
  const alert = document.getElementById('alertBanner');
  if (!alert) return;

  // Auto-dismiss success after 4 s
  if (alert.classList.contains('alert-success')) {
    setTimeout(() => {
      alert.style.transition = 'opacity .4s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 420);
    }, 4000);
  }

  // Allow manual dismiss by clicking
  alert.style.cursor = 'pointer';
  alert.title = 'Click to dismiss';
  alert.addEventListener('click', () => {
    alert.style.transition = 'opacity .25s';
    alert.style.opacity = '0';
    setTimeout(() => alert.remove(), 280);
  });
})();

/* ══════════════════════════════════════════════
   WARN ON UNSAVED CHANGES (page leave)
   ══════════════════════════════════════════════ */
(function initLeaveGuard() {
  const form = document.getElementById('settingsForm');
  if (!form) return;

  let submitting = false;
  form.addEventListener('submit', () => { submitting = true; });

  window.addEventListener('beforeunload', e => {
    if (submitting) return;
    const dirty = form.querySelectorAll('.is-dirty');
    if (dirty.length > 0) {
      e.preventDefault();
      e.returnValue = '';
    }
  });
})();
