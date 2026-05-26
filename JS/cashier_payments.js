/* =============================================
   EVSU RESERVE — cashier_payments.js
   Payment Verification: modal, AJAX actions,
   live search, toast notifications, sidebar
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
    closeModal();
  }
});

/* ══════════════════════════════════════════════
   TOAST
   ══════════════════════════════════════════════ */
let toastTimer = null;
function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.className   = `toast ${type} show`;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
}

/* ══════════════════════════════════════════════
   STATUS BADGE HTML helper
   ══════════════════════════════════════════════ */
function badgeHTML(status) {
  const map = {
    pending:  { label: 'Pending',  cls: 'badge-orange' },
    verified: { label: 'Verified', cls: 'badge-green'  },
    rejected: { label: 'Rejected', cls: 'badge-red'    },
  };
  const cfg = map[status] || { label: status, cls: 'badge-gray' };
  return `<span class="badge ${cfg.cls}">${cfg.label}</span>`;
}

/* ══════════════════════════════════════════════
   MODAL
   ══════════════════════════════════════════════ */
let currentPayment = null;

function openModal(paymentJson) {
  let payment;
  try {
    payment = typeof paymentJson === 'string' ? JSON.parse(paymentJson) : paymentJson;
  } catch {
    return;
  }
  currentPayment = payment;

  const backdrop = document.getElementById('modalBackdrop');
  const body     = document.getElementById('modalBody');
  const footer   = document.getElementById('modalFooter');

  // Build detail grid
  const ref  = payment.reference_number || '—';
  const date = payment.created_date
    ? new Date(payment.created_date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
    : '—';

  body.innerHTML = `
    <div class="detail-grid">
      <div class="detail-item">
        <span class="detail-label">Order</span>
        <span class="detail-value">${esc(payment.order_number || '—')}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Amount</span>
        <span class="detail-value highlight">₱${Number(payment.amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Payer</span>
        <span class="detail-value">${esc(payment.payer_name || '—')}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Method</span>
        <span class="detail-value">${esc(payment.method || '—')}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Reference</span>
        <span class="detail-value">${esc(ref)}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Status</span>
        <div style="margin-top:.25rem">${badgeHTML(payment.status)}</div>
      </div>
      <div class="detail-item">
        <span class="detail-label">Date</span>
        <span class="detail-value">${esc(date)}</span>
      </div>
    </div>

    <div class="proof-wrap">
      <span class="proof-label">Proof of Payment</span>
      ${payment.proof_url
        ? `<img src="${esc(payment.proof_url)}" alt="Proof of payment"/>`
        : `<div class="proof-placeholder">No proof image uploaded</div>`
      }
    </div>
  `;

  // Footer action buttons — only for pending
  footer.innerHTML = '';
  if (payment.status === 'pending') {
    footer.innerHTML = `
      <button class="btn-modal btn-modal-verify" onclick="handleAction('${esc(payment.id)}','verified','${esc(payment.order_id)}')">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
          <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        Verify
      </button>
      <button class="btn-modal btn-modal-reject" onclick="handleAction('${esc(payment.id)}','rejected','${esc(payment.order_id)}')">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <line x1="15" y1="9" x2="9" y2="15"/>
          <line x1="9"  y1="9" x2="15" y2="15"/>
        </svg>
        Reject
      </button>
    `;
  }

  backdrop.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal(e) {
  // Close only if clicking the backdrop itself (not the modal card)
  if (e && e.target !== document.getElementById('modalBackdrop')) return;
  _closeModal();
}
function _closeModal() {
  document.getElementById('modalBackdrop').classList.remove('open');
  document.body.style.overflow = '';
  currentPayment = null;
}
// Wire the X button (called directly, no event)
window.closeModal = function(e) {
  if (!e) { _closeModal(); return; }
  closeModal(e);
};

/* ══════════════════════════════════════════════
   VERIFY / REJECT  (AJAX → PHP POST)
   ══════════════════════════════════════════════ */
function handleAction(paymentId, action, orderId) {
  const label = action === 'verified' ? 'Verify' : 'Reject';
  if (!confirm(`${label} this payment?`)) return;

  const formData = new FormData();
  formData.append('action',     action);
  formData.append('payment_id', paymentId);
  formData.append('order_id',   orderId || '');

  fetch(window.location.pathname, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast(
          action === 'verified' ? 'Payment verified successfully!' : 'Payment rejected.',
          action === 'verified' ? 'success' : 'error'
        );
        _closeModal();
        updateRowStatus(data.payment_id, data.new_status);
      } else {
        showToast('Action failed. Please try again.', 'error');
      }
    })
    .catch(() => showToast('Network error. Please try again.', 'error'));
}

/* Update the table row without a full page reload */
function updateRowStatus(paymentId, newStatus) {
  // Find the row by matching the payment id inside its data-payment JSON
  const rows = document.querySelectorAll('.orders-table tbody tr');
  rows.forEach(row => {
    try {
      const p = JSON.parse(row.dataset.payment || '{}');
      if (p.id !== paymentId) return;

      // Update badge
      const badgeCell = row.querySelector('.badge');
      if (badgeCell) {
        const cfg = { verified: ['Verified','badge-green'], rejected: ['Rejected','badge-red'], pending: ['Pending','badge-orange'] };
        const [label, cls] = cfg[newStatus] || [newStatus, 'badge-gray'];
        badgeCell.textContent = label;
        badgeCell.className   = `badge ${cls}`;
      }

      // Remove verify/reject buttons
      row.querySelectorAll('.btn-verify, .btn-reject').forEach(btn => btn.remove());

      // Update stored data
      p.status = newStatus;
      row.dataset.payment = JSON.stringify(p);
    } catch { /* skip */ }
  });
}

/* ══════════════════════════════════════════════
   LIVE CLIENT-SIDE SEARCH
   (supplements server-side for instant feedback)
   ══════════════════════════════════════════════ */
(function initLiveSearch() {
  const input = document.querySelector('.search-input');
  if (!input) return;

  input.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.orders-table tbody tr').forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(q) ? '' : 'none';
    });
  });
})();

/* ══════════════════════════════════════════════
   UTILITY
   ══════════════════════════════════════════════ */
function esc(str) {
  return String(str ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[c]));
}
