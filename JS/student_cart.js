/* =============================================
   EVSU RESERVE — cart.js
   ============================================= */

// Local mirror of cart quantities & prices (seeded from PHP)
const cart = {};
CART_DATA.forEach(item => {
  cart[item.id] = { price: parseFloat(item.unit_price), qty: item.quantity };
});

/* ── Quantity change ── */
function changeQty(itemId, delta) {
  const entry = cart[itemId];
  if (!entry) return;

  const newQty = Math.max(1, entry.qty + delta);
  entry.qty = newQty;

  // Update DOM
  document.getElementById(`qty-${itemId}`).textContent = newQty;
  document.getElementById(`subtotal-${itemId}`).textContent =
    '₱' + (entry.price * newQty).toLocaleString('en-PH', { minimumFractionDigits: 2 });

  recalcTotal();

  // Persist to server
  fetch('cart_update.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ item_id: itemId, quantity: newQty }),
  }).catch(() => {});
}

/* ── Remove item ── */
function removeItem(itemId) {
  const row = document.getElementById(`row-${itemId}`);
  if (!row) return;

  // Animate out
  row.style.transition = 'opacity .25s, transform .25s';
  row.style.opacity    = '0';
  row.style.transform  = 'translateX(20px)';

  setTimeout(() => {
    row.remove();
    delete cart[itemId];
    recalcTotal();
    updateItemCount();
  }, 260);

  fetch('cart_remove.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ item_id: itemId }),
  }).catch(() => {});
}

/* ── Recalculate totals ── */
function recalcTotal() {
  let total = 0;
  let count = 0;
  Object.values(cart).forEach(({ price, qty }) => {
    total += price * qty;
    count += qty;
  });

  const fmt = (n) => '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2 });
  document.getElementById('display-subtotal').textContent = fmt(total);
  document.getElementById('display-total').textContent    = fmt(total);

  // Update sidebar/topbar badges
  document.querySelectorAll('.nav-badge, .cart-dot').forEach(el => {
    el.textContent = count;
    el.style.display = count > 0 ? 'flex' : 'none';
  });
}

/* ── Update "X items in your cart" subtitle ── */
function updateItemCount() {
  const count = Object.values(cart).reduce((s, { qty }) => s + qty, 0);
  const sub   = document.querySelector('.page-sub');
  if (sub) sub.textContent = count + ' item' + (count !== 1 ? 's' : '') + ' in your cart';
}

/* ── Toggle proof-of-payment section ── */
function toggleProof(method) {
  const section = document.getElementById('proof-section');
  if (!section) return;
  section.style.display = method === 'cash' ? 'block' : 'none';
}

/* ── Update file name display ── */
function updateFileName(input) {
  const label   = document.getElementById('file-upload-label');
  const display = document.getElementById('file-name-display');
  if (input.files && input.files[0]) {
    display.textContent = input.files[0].name;
    label.classList.add('has-file');
  } else {
    display.textContent = 'Choose file...';
    label.classList.remove('has-file');
  }
}

/* ── Checkout form: loading state ── */
(function initCheckout() {
  const form = document.getElementById('checkout-form');
  const btn  = document.getElementById('place-order-btn');
  if (!form || !btn) return;

  form.addEventListener('submit', function () {
    btn.disabled = true;
    btn.innerHTML = `
      <div class="btn-spinner"></div>
      Processing...
    `;
  });
})();

/* ── Toast ── */
let toastTimer;
function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = message;
  toast.className   = `toast toast-${type} show`;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
}