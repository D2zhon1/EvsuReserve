/* =============================================
   EVSU RESERVE — product_management.js
   ============================================= */

/* ── State ── */
let deleteTargetId   = null;
let currentSizes     = [];

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
  document.getElementById('form-stock').value        = product.stock_quantity || '';
  document.getElementById('form-image').value        = product.image_url    || '';
  document.getElementById('form-active').checked     = product.is_active !== false;

  // Sizes
  currentSizes = Array.isArray(product.sizes_available) ? [...product.sizes_available] : [];
  renderSizeTags();

  document.getElementById('modal-backdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
}

/* ─────────────────────────────────────────────
   SIZES
───────────────────────────────────────────── */
function addSize() {
  const input = document.getElementById('size-input');
  const val   = input.value.trim().toUpperCase();
  if (val && !currentSizes.includes(val)) {
    currentSizes.push(val);
    renderSizeTags();
  }
  input.value = '';
  input.focus();
}

function removeSize(size) {
  currentSizes = currentSizes.filter(s => s !== size);
  renderSizeTags();
}

function renderSizeTags() {
  const container = document.getElementById('size-tags');
  container.innerHTML = currentSizes.map(s =>
    `<span class="size-tag" onclick="removeSize('${s}')">
       ${s} <span class="size-tag-x">×</span>
     </span>`
  ).join('');
  document.getElementById('form-sizes').value = JSON.stringify(currentSizes);
}

/* ─────────────────────────────────────────────
   FORM SUBMIT
───────────────────────────────────────────── */
function handleSubmit(e) {
  e.preventDefault();

  const form     = e.target;
  const id       = document.getElementById('form-id').value;
  const isEdit   = Boolean(id);
  const btn      = document.getElementById('submit-btn');

  const payload = {
    id:               id || null,
    name:             form.name.value.trim(),
    description:      form.description.value.trim(),
    category:         form.category.value,
    sku:              form.sku.value.trim(),
    price:            parseFloat(form.price.value) || 0,
    markup_price:     parseFloat(form.markup_price.value) || 0,
    stock_quantity:   parseInt(form.stock_quantity.value) || 0,
    image_url:        form.image_url.value.trim(),
    sizes_available:  currentSizes,
    is_active:        form.is_active.checked,
  };

  btn.disabled    = true;
  btn.textContent = 'Saving…';

  // ── Replace this block with a real fetch() to your PHP endpoint ──
  setTimeout(() => {
    closeModal();
    showToast(isEdit ? 'Product updated successfully.' : 'Product created successfully.', 'success');
    btn.disabled    = false;
    btn.textContent = isEdit ? 'Update Product' : 'Create Product';

    // TODO: submit payload via fetch to product_save.php, then reload table
    console.log('Payload to save:', payload);
  }, 600);
  // ─────────────────────────────────────────────────────────────────
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

  // ── Replace with real fetch() to product_delete.php ──
  setTimeout(() => {
    showToast('Product deleted.', 'success');
    closeDelete();
    btn.disabled    = false;
    btn.textContent = 'Delete';
    // TODO: remove row from DOM or reload table
    console.log('Delete product ID:', deleteTargetId);
  }, 500);
  // ──────────────────────────────────────────────────────
}

/* ─────────────────────────────────────────────
   RESET FORM
───────────────────────────────────────────── */
function resetForm() {
  document.getElementById('product-form').reset();
  document.getElementById('form-id').value = '';
  currentSizes = [];
  renderSizeTags();
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

/* ── Close modal on Escape ── */
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    closeModal();
    closeDelete();
  }
});