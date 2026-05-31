/* =============================================
   EVSU RESERVE — product_management.js
   ============================================= */

/* ── State ── */
let deleteTargetId = null;
let currentSizes = [];
let sizeStockMap = {};

/* ─────────────────────────────────────────────
   SEARCH / FILTER
───────────────────────────────────────────── */
function filterProducts() {
  const q     = document.getElementById('search-input').value.toLowerCase().trim();
  const rows  = document.querySelectorAll('#products-table tbody tr');
  let   count = 0;

  rows.forEach(row => {
    const name = row.dataset.name     || '';
    const cat  = row.dataset.category || '';
    const match = !q || name.includes(q) || cat.includes(q);
    row.style.display = match ? '' : 'none';
    if (match) count++;
  });

  const countEl = document.getElementById('product-count');
  if (countEl) countEl.textContent = count + (count === 1 ? ' product' : ' products');
}

/* ─────────────────────────────────────────────
   MODAL — open / close
───────────────────────────────────────────── */
function openModal() {
  resetForm();
  document.getElementById('modal-title').textContent  = 'Add New Product';
  document.getElementById('submit-btn').textContent   = 'Create Product';
  document.getElementById('modal-backdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('modal-backdrop').classList.remove('open');
  document.body.style.overflow = '';
  resetForm();
}

function handleBackdropClick(e) {
  if (e.target === document.getElementById('modal-backdrop')) closeModal();
}

/* ─────────────────────────────────────────────
   EDIT
───────────────────────────────────────────── */
function openEdit(product) {
  resetForm();

  document.getElementById('modal-title').textContent = 'Edit Product';
  document.getElementById('submit-btn').textContent  = 'Update Product';
  document.getElementById('form-id').value           = product.id;
  document.getElementById('form-name').value         = product.name         || '';
  document.getElementById('form-desc').value         = product.description  || '';
  document.getElementById('form-category').value     = product.category     || 'uniform';
  document.getElementById('form-sku').value          = product.sku          || '';
  document.getElementById('form-price').value        = product.price        || '';
  document.getElementById('form-markup').value       = product.markup_price || '';
  document.getElementById('form-image').value        = product.image_url    || '';
  document.getElementById('form-active').checked     = product.is_active !== false;

  currentSizes = Array.isArray(product.sizes_available) ? [...product.sizes_available] : [];
  sizeStockMap = (product.size_stock && typeof product.size_stock === 'object')
    ? { ...product.size_stock }
    : {};

  currentSizes.forEach(s => {
    if (sizeStockMap[s] === undefined) {
      sizeStockMap[s] = 0;
    }
  });

  if (currentSizes.length === 0) {
    document.getElementById('form-stock').value = product.stock_quantity || '';
  }

  renderSizeStockUI();

  document.getElementById('modal-backdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
}

/* ─────────────────────────────────────────────
   SIZES + PER-SIZE STOCK
───────────────────────────────────────────── */
function addSizePreset(size) {
  const val = String(size).trim().toUpperCase();
  if (!val || currentSizes.includes(val)) {
    return;
  }
  currentSizes.push(val);
  if (sizeStockMap[val] === undefined) {
    sizeStockMap[val] = 0;
  }
  renderSizeStockUI();
}

function addSize() {
  const input = document.getElementById('size-input');
  const val   = input.value.trim().toUpperCase();
  if (val && !currentSizes.includes(val)) {
    currentSizes.push(val);
    sizeStockMap[val] = 0;
    renderSizeStockUI();
  }
  input.value = '';
  input.focus();
}

function removeSize(size) {
  currentSizes = currentSizes.filter(s => s !== size);
  delete sizeStockMap[size];
  renderSizeStockUI();
}

function updateSizeStock(size, value) {
  sizeStockMap[size] = Math.max(0, parseInt(value, 10) || 0);
  updateSizeStockTotal();
}

function updateSizeStockTotal() {
  const total = currentSizes.reduce((sum, s) => sum + (sizeStockMap[s] || 0), 0);
  const el = document.getElementById('size-stock-total');
  if (el) el.textContent = total;
}

function syncStockFieldsVisibility() {
  const hasSizes = currentSizes.length > 0;
  const generalWrap = document.getElementById('general-stock-wrap');
  const tableWrap = document.getElementById('size-stock-table-wrap');

  if (generalWrap) {
    generalWrap.style.display = hasSizes ? 'none' : '';
  }
  if (tableWrap) {
    tableWrap.style.display = hasSizes ? 'block' : 'none';
  }
}

function renderSizeStockUI() {
  const tbody = document.getElementById('size-stock-rows');
  if (!tbody) return;

  tbody.innerHTML = currentSizes.map(s => `
    <tr>
      <td><span class="size-label-badge">${escHtml(s)}</span></td>
      <td>
        <input type="number" class="form-input size-stock-input" min="0" step="1"
               value="${sizeStockMap[s] ?? 0}"
               onchange="updateSizeStock('${escAttr(s)}', this.value)"
               oninput="updateSizeStock('${escAttr(s)}', this.value)"/>
      </td>
      <td>
        <button type="button" class="icon-btn icon-btn-delete size-remove-btn"
                onclick="removeSize('${escAttr(s)}')" title="Remove size">×</button>
      </td>
    </tr>
  `).join('');

  document.getElementById('form-sizes').value = JSON.stringify(currentSizes);
  updateSizeStockTotal();
  syncStockFieldsVisibility();
}

function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function escAttr(str) {
  return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

/* ─────────────────────────────────────────────
   FORM SUBMIT
───────────────────────────────────────────── */
function handleSubmit(e) {
  e.preventDefault();

  const form   = e.target;
  const id     = document.getElementById('form-id').value;
  const isEdit = Boolean(id);
  const btn    = document.getElementById('submit-btn');

  const hasSizes = currentSizes.length > 0;
  let stockQty = parseInt(form.stock_quantity.value, 10) || 0;
  if (hasSizes) {
    stockQty = currentSizes.reduce((sum, s) => sum + (sizeStockMap[s] || 0), 0);
  }

  const payload = {
    id:               id || null,
    name:             form.name.value.trim(),
    description:      form.description.value.trim(),
    category:         form.category.value,
    sku:              form.sku.value.trim(),
    price:            parseFloat(form.price.value) || 0,
    markup_price:     parseFloat(form.markup_price.value) || 0,
    stock_quantity:   stockQty,
    size_stock:       hasSizes ? sizeStockMap : {},
    image_url:        form.image_url.value.trim(),
    sizes_available:  currentSizes,
    is_active:        form.is_active.checked,
  };

  btn.disabled    = true;
  btn.textContent = 'Saving…';

  fetch('product_save.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
    .then(async (r) => {
      const text = await r.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch {
        throw new Error(text.slice(0, 200) || `Server error (${r.status})`);
      }
      if (!r.ok) {
        throw new Error(data.message || `Server error (${r.status})`);
      }
      return data;
    })
    .then(data => {
      if (data.success) {
        closeModal();
        showToast(isEdit ? 'Product updated successfully.' : 'Product created successfully.', 'success');
        setTimeout(() => location.reload(), 800);
      } else {
        showToast(data.message || 'Save failed.', 'error');
      }
    })
    .catch((err) => showToast(err.message || 'Network error.', 'error'))
    .finally(() => {
      btn.disabled    = false;
      btn.textContent = isEdit ? 'Update Product' : 'Create Product';
    });
}

/* ─────────────────────────────────────────────
   DELETE
───────────────────────────────────────────── */
function confirmDelete(id, name) {
  deleteTargetId = id;
  document.getElementById('delete-name').textContent = name;
  document.getElementById('delete-backdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeDelete() {
  deleteTargetId = null;
  document.getElementById('delete-backdrop').classList.remove('open');
  document.body.style.overflow = '';
}

function handleDeleteBackdropClick(e) {
  if (e.target === document.getElementById('delete-backdrop')) closeDelete();
}

function executeDelete() {
  if (!deleteTargetId) return;
  const btn = document.getElementById('confirm-delete-btn');
  btn.disabled    = true;
  btn.textContent = 'Deleting…';

  fetch('product_delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: deleteTargetId }),
  })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast('Product deleted.', 'success');
        closeDelete();
        setTimeout(() => location.reload(), 600);
      } else {
        showToast(data.message || 'Delete failed.', 'error');
      }
    })
    .catch(() => showToast('Network error.', 'error'))
    .finally(() => {
      btn.disabled    = false;
      btn.textContent = 'Delete';
    });
}

/* ─────────────────────────────────────────────
   RESET FORM
───────────────────────────────────────────── */
function resetForm() {
  document.getElementById('product-form').reset();
  document.getElementById('form-id').value = '';
  currentSizes = [];
  sizeStockMap = {};
  renderSizeStockUI();
}

/* ─────────────────────────────────────────────
   TOAST
───────────────────────────────────────────── */
function showToast(msg, type = 'success') {
  const container = document.getElementById('toast-container');
  const toast     = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<span class="toast-dot"></span>${msg}`;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3500);
}

document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    closeModal();
    closeDelete();
  }
});
