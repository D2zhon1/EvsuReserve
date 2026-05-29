/**
 * EVSU RESERVE — order_management.js
 *
 * Handles:
 *  - Live search + status filter
 *  - Inline status update (with toast feedback)
 *  - Order detail modal (open / close / keyboard)
 */

(function () {
  'use strict';

  /* ── Badge class helpers (mirror PHP) ──────────────────────── */
  const STATUS_BADGE = {
    completed:  'badge-green',
    pending:    'badge-orange',
    processing: 'badge-blue',
    paid:       'badge-blue',
    ready:      'badge-purple',
    cancelled:  'badge-red',
  };

  const PAYMENT_BADGE = {
    paid:     'badge-green',
    pending:  'badge-orange',
    refunded: 'badge-gray',
  };

  function statusBadgeClass(s) { return STATUS_BADGE[s] || 'badge-gray'; }
  function paymentBadgeClass(s) { return PAYMENT_BADGE[s] || 'badge-gray'; }

  /* ── Format date  ───────────────────────────────────────────── */
  const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  function fmtDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return MONTHS[d.getMonth()] + ' ' + d.getDate();
  }

  /* ══ FILTER LOGIC ════════════════════════════════════════════ */
  window.filterOrders = function () {
    const search = document.getElementById('om-search').value.trim().toLowerCase();
    const status = document.getElementById('om-status-filter').value;
    const rows   = document.querySelectorAll('#om-tbody .om-row');
    let visible  = 0;

    rows.forEach(function (row) {
      const order = JSON.parse(row.dataset.order);
      const matchStatus = status === 'all' || order.status === status;
      const matchSearch = !search ||
        order.order_number.toLowerCase().includes(search) ||
        order.customer_name.toLowerCase().includes(search);

      const show = matchStatus && matchSearch;
      row.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    document.getElementById('om-empty').style.display      = visible === 0 ? 'flex' : 'none';
    document.getElementById('om-table-wrap').style.display = visible > 0  ? ''     : 'none';
  };

  /* ══ INLINE STATUS UPDATE ════════════════════════════════════ */
  window.updateStatus = function (selectEl) {
    const orderId   = selectEl.dataset.id;
    const newStatus = selectEl.value;
    const row       = selectEl.closest('tr');
    const cells     = row.querySelectorAll('td');
    const badgeCell = cells[5]; // "Status" column

    // Keep previous status to allow revert on error
    const record = ORDER_DATA.find(function (o) { return o.id === orderId; });
    const prevStatus = record ? record.status : null;

    // Disable select while saving
    selectEl.disabled = true;

    // Optimistic UI update
    if (badgeCell) {
      badgeCell.innerHTML =
        '<span class="badge ' + statusBadgeClass(newStatus) + '">' +
        newStatus.charAt(0).toUpperCase() + newStatus.slice(1) +
        '</span>';
    }
    if (record) record.status = newStatus;
    if (record) row.dataset.order = JSON.stringify(record);

    row.classList.remove('om-row-updated');
    void row.offsetWidth;
    row.classList.add('om-row-updated');

    // Call server endpoint
    fetch('update_order_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: orderId, status: newStatus }),
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (!data || !data.success) {
        // Revert UI
        if (badgeCell && prevStatus) {
          badgeCell.innerHTML = '<span class="badge ' + statusBadgeClass(prevStatus) + '">' + prevStatus.charAt(0).toUpperCase() + prevStatus.slice(1) + '</span>';
        }
        if (record) { record.status = prevStatus; row.dataset.order = JSON.stringify(record); }
        selectEl.value = prevStatus || selectEl.value;
        showToast('Update failed', 'error');
      } else {
        showToast('Order ' + (record ? record.order_number : orderId) + ' updated to ' + newStatus, 'success');
      }
    })
    .catch(function () {
      // Network error — revert
      if (badgeCell && prevStatus) {
        badgeCell.innerHTML = '<span class="badge ' + statusBadgeClass(prevStatus) + '">' + prevStatus.charAt(0).toUpperCase() + prevStatus.slice(1) + '</span>';
      }
      if (record) { record.status = prevStatus; row.dataset.order = JSON.stringify(record); }
      selectEl.value = prevStatus || selectEl.value;
      showToast('Network error', 'error');
    })
    .finally(function () {
      selectEl.disabled = false;
    });
  };

  /* ══ ORDER DETAIL MODAL ══════════════════════════════════════ */
  var activeOrder = null;

  window.openOrderModal = function (rowEl) {
    activeOrder = JSON.parse(rowEl.dataset.order);
    renderModal(activeOrder);

    const backdrop = document.getElementById('om-modal-backdrop');
    backdrop.classList.add('open');
    document.body.style.overflow = 'hidden';

    // Focus close button for accessibility
    setTimeout(function () {
      var closeBtn = backdrop.querySelector('.om-modal-close');
      if (closeBtn) closeBtn.focus();
    }, 50);
  };

  window.closeOrderModal = function () {
    document.getElementById('om-modal-backdrop').classList.remove('open');
    document.body.style.overflow = '';
    activeOrder = null;
  };

  function renderModal(order) {
    document.getElementById('om-modal-title').textContent =
      'Order Details: ' + order.order_number;

    // Build items HTML
    var itemsHtml = '';
    (order.items || []).forEach(function (item) {
      var label = item.product_name +
        (item.size ? ' (' + item.size + ')' : '') +
        ' ×' + item.quantity;
      itemsHtml +=
        '<div class="om-item-row">' +
          '<span class="om-item-name">' + escHtml(label) + '</span>' +
          '<span class="om-item-qty">' + item.quantity + ' pc' + (item.quantity !== 1 ? 's' : '') + '</span>' +
          '<span class="om-item-subtotal">₱' + Number(item.subtotal).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span>' +
        '</div>';
    });

    var notesHtml = order.notes
      ? '<p class="om-notes">' + escHtml(order.notes) + '</p>'
      : '';

    document.getElementById('om-modal-body').innerHTML =
      '<div class="om-info-grid">' +
        '<div class="om-info-item">' +
          '<span class="om-info-label">Customer</span>' +
          '<span class="om-info-value">' + escHtml(order.customer_name) + '</span>' +
        '</div>' +
        '<div class="om-info-item">' +
          '<span class="om-info-label">Email</span>' +
          '<span class="om-info-value">' + escHtml(order.customer_email) + '</span>' +
        '</div>' +
        '<div class="om-info-item">' +
          '<span class="om-info-label">Total Amount</span>' +
          '<span class="om-info-value total">₱' + Number(order.total_amount).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span>' +
        '</div>' +
        '<div class="om-info-item">' +
          '<span class="om-info-label">Payment</span>' +
          '<span class="om-info-value">' +
            escHtml(order.payment_method) + ' — ' +
            '<span class="badge ' + paymentBadgeClass(order.payment_status) + '">' +
              order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1) +
            '</span>' +
          '</span>' +
        '</div>' +
        (order.status === 'pending' && order.payment_method === 'Cash'
          ? '<div class="om-info-item" style="grid-column:1/-1">' +
              '<span class="om-info-label">Staff action</span>' +
              '<span class="om-info-value">Awaiting staff confirmation before cashier payment.</span>' +
            '</div>'
          : '') +
        '<div class="om-info-item">' +
          '<span class="om-info-label">Order Status</span>' +
          '<span class="om-info-value">' +
            '<span class="badge ' + statusBadgeClass(order.status) + '">' +
              order.status.charAt(0).toUpperCase() + order.status.slice(1) +
            '</span>' +
          '</span>' +
        '</div>' +
        '<div class="om-info-item">' +
          '<span class="om-info-label">Date Placed</span>' +
          '<span class="om-info-value">' + fmtDate(order.created_date) + '</span>' +
        '</div>' +
      '</div>' +

      '<p class="om-items-heading">Order Items</p>' +
      '<div class="om-items-list">' + itemsHtml + '</div>' +

      notesHtml;
  }

  /* ── Escape HTML for safe DOM insertion ─────────────────────── */
  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ── Close modal on Escape ──────────────────────────────────── */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeOrderModal();
  });

  /* ── Init ────────────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    // Wire empty-state initial visibility
    filterOrders();
  });

})();

/* ─────────────────────────────────────────────────────────────
   Small toast helper (creates container dynamically)
───────────────────────────────────────────────────────────── */
function showToast(message, type = 'success') {
  var container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.style.position = 'fixed';
    container.style.right = '18px';
    container.style.bottom = '18px';
    container.style.zIndex = 9999;
    document.body.appendChild(container);
  }
  var toast = document.createElement('div');
  toast.className = 'toast toast-' + type;
  toast.style.marginTop = '8px';
  toast.style.padding = '10px 14px';
  toast.style.borderRadius = '10px';
  toast.style.boxShadow = '0 8px 20px rgba(0,0,0,.12)';
  toast.style.fontWeight = '600';
  toast.style.fontSize = '0.92rem';
  toast.style.color = type === 'error' ? '#fff' : '#fff';
  toast.style.background = type === 'error' ? '#b91c1c' : '#15803d';
  toast.textContent = message;
  container.appendChild(toast);
  setTimeout(function () { toast.remove(); }, 3600);
}