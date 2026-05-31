/* =============================================
   EVSU RESERVE — student_product.js
   ============================================= */

const selectedSizes = {};

function pidKey(productId) {
  return String(productId);
}

function selectSize(btn) {
  if (btn.disabled) return;

  const productId = pidKey(btn.dataset.product);
  const size      = btn.dataset.size;
  const stock     = parseInt(btn.dataset.stock, 10) || 0;

  document.querySelectorAll(`.size-btn[data-product="${btn.dataset.product}"]`)
    .forEach(b => b.classList.remove('selected'));

  btn.classList.add('selected');
  selectedSizes[productId] = size;
  selectedSizes[productId + '_stock'] = stock;
}

function parseSizeStock(raw) {
  if (!raw) return {};
  try {
    const data = JSON.parse(raw);
    return data && typeof data === 'object' && !Array.isArray(data) ? data : {};
  } catch {
    return {};
  }
}

function addToCart(btnEl) {
  const card = btnEl.closest('.product-card');
  if (!card) return;

  const productId   = pidKey(card.dataset.id);
  const productName = btnEl.dataset.productName || '';
  const price       = parseFloat(btnEl.dataset.price) || 0;
  const hasSizes    = card.dataset.hasSizes === '1';

  if (hasSizes && !selectedSizes[productId]) {
    const availableBtns = document.querySelectorAll(
      `.size-btn[data-product="${card.dataset.id}"]:not(:disabled)`
    );
    if (availableBtns.length === 1) {
      selectSize(availableBtns[0]);
    } else {
      showToast('Please select a size first.', 'error');
      document.querySelectorAll(`.size-btn[data-product="${card.dataset.id}"]`)
        .forEach(b => b.classList.add('size-required'));
      setTimeout(() => {
        document.querySelectorAll(`.size-btn[data-product="${card.dataset.id}"]`)
          .forEach(b => b.classList.remove('size-required'));
      }, 1200);
      return;
    }
  }

  const size = selectedSizes[productId] || '';

  if (hasSizes) {
    const sizeStock = parseSizeStock(btnEl.getAttribute('data-size-stock'));
    const stockLeft = Object.keys(sizeStock).length
      ? (sizeStock[size] ?? 0)
      : (selectedSizes[productId + '_stock'] ?? 0);
    if (stockLeft <= 0) {
      showToast(`Size ${size} is out of stock.`, 'error');
      return;
    }
  }

  if (!productName || price <= 0) {
    showToast('This product has no valid price. Contact the bookstore.', 'error');
    return;
  }

  fetch('cart_add.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    credentials: 'same-origin',
    body: new URLSearchParams({
      product_id:   card.dataset.id,
      product_name: productName,
      unit_price:   price,
      size:         size,
      quantity:     1,
    }),
  })
    .then(async (res) => {
      const text = await res.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch {
        throw new Error('Server returned an invalid response.');
      }
      return data;
    })
    .then(data => {
      if (data.success) {
        showToast(`"${productName}" added to cart!`, 'success');
        updateCartBadge(data.cart_count);
      } else {
        showToast(data.message || 'Failed to add to cart.', 'error');
      }
    })
    .catch((err) => {
      showToast(err.message || 'Could not add to cart. Please log in and try again.', 'error');
    });
}

function updateCartBadge(count) {
  document.querySelectorAll('.nav-badge, .cart-dot').forEach(el => {
    el.textContent = count;
    el.style.display = count > 0 ? 'flex' : 'none';
  });
}

let toastTimer;
function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = message;
  toast.className   = `toast toast-${type} show`;

  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toast.classList.remove('show');
  }, 2800);
}

(function initSearch() {
  const input = document.querySelector('.search-input');
  if (!input) return;

  input.removeAttribute('oninput');

  let timer;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => input.form.submit(), 400);
  });
})();
