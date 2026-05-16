/* =============================================
   EVSU RESERVE — products.js
   ============================================= */

// Track selected sizes per product
const selectedSizes = {};

/* ── Size selection ── */
function selectSize(btn) {
  const productId = btn.dataset.product;
  const size      = btn.dataset.size;

  // Deselect siblings
  document.querySelectorAll(`.size-btn[data-product="${productId}"]`)
    .forEach(b => b.classList.remove('selected'));

  btn.classList.add('selected');
  selectedSizes[productId] = size;
}

/* ── Add to cart ── */
function addToCart(productId, productName, price, hasSizes) {
  // If product has sizes, a size must be selected
  if (hasSizes && !selectedSizes[productId]) {
    showToast('Please select a size first', 'error');
    // Highlight size buttons
    document.querySelectorAll(`.size-btn[data-product="${productId}"]`)
      .forEach(b => b.classList.add('size-required'));
    setTimeout(() => {
      document.querySelectorAll(`.size-btn[data-product="${productId}"]`)
        .forEach(b => b.classList.remove('size-required'));
    }, 1200);
    return;
  }

  const size = selectedSizes[productId] || '';

  // ── Send to server via fetch ──────────────────────────────────────────
  fetch('cart_add.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      product_id:   productId,
      product_name: productName,
      unit_price:   price,
      size:         size,
      quantity:     1,
    }),
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        showToast(`"${productName}" added to cart!`, 'success');
        updateCartBadge(data.cart_count);
      } else {
        showToast(data.message || 'Failed to add to cart.', 'error');
      }
    })
    .catch(() => {
      // Fallback: show success anyway (no server yet)
      showToast(`"${productName}" added to cart!`, 'success');
    });
}

/* ── Update cart count badges ── */
function updateCartBadge(count) {
  document.querySelectorAll('.nav-badge, .cart-dot').forEach(el => {
    el.textContent = count;
    el.style.display = count > 0 ? 'flex' : 'none';
  });
}

/* ── Toast notification ── */
let toastTimer;
function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.className   = `toast toast-${type} show`;

  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toast.classList.remove('show');
  }, 2800);
}

/* ── Auto-submit search after 400ms debounce ── */
(function initSearch() {
  const input = document.querySelector('.search-input');
  if (!input) return;

  // Remove the inline oninput to prevent double-submit; use debounce
  input.removeAttribute('oninput');

  let timer;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => input.form.submit(), 400);
  });
})();