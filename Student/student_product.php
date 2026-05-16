<?php
// ─── Sample product data (replace with your DB query) ───────────────────────
$products = [
  [
    'id'              => 1,
    'name'            => 'School Uniform (Boys)',
    'description'     => 'Standard polo shirt with school emblem, durable and comfortable.',
    'category'        => 'uniform',
    'price'           => 350,
    'markup_price'    => 380,
    'stock_quantity'  => 24,
    'image_url'       => '',
    'sizes_available' => ['XS','S','M','L','XL','XXL'],
  ],
  [
    'id'              => 2,
    'name'            => 'School Uniform (Girls)',
    'description'     => 'Pleated skirt set with school emblem, neat finish.',
    'category'        => 'uniform',
    'price'           => 370,
    'markup_price'    => 400,
    'stock_quantity'  => 18,
    'image_url'       => '',
    'sizes_available' => ['XS','S','M','L','XL'],
  ],
  [
    'id'              => 3,
    'name'            => 'ID Sling (Standard)',
    'description'     => 'Adjustable lanyard with school logo print.',
    'category'        => 'id_sling',
    'price'           => 55,
    'markup_price'    => 65,
    'stock_quantity'  => 80,
    'image_url'       => '',
    'sizes_available' => [],
  ],
  [
    'id'              => 4,
    'name'            => 'Student Handbook 2025',
    'description'     => 'Official student handbook for the current school year.',
    'category'        => 'booklet',
    'price'           => 90,
    'markup_price'    => 100,
    'stock_quantity'  => 0,
    'image_url'       => '',
    'sizes_available' => [],
  ],
  [
    'id'              => 5,
    'name'            => 'Ballpen Set (12 pcs)',
    'description'     => 'Smooth-writing blue ballpens, school-issued.',
    'category'        => 'school_supply',
    'price'           => 60,
    'markup_price'    => 70,
    'stock_quantity'  => 150,
    'image_url'       => '',
    'sizes_available' => [],
  ],
  [
    'id'              => 6,
    'name'            => 'School Tote Bag',
    'description'     => 'Canvas tote with school name print — eco-friendly.',
    'category'        => 'merchandise',
    'price'           => 120,
    'markup_price'    => 140,
    'stock_quantity'  => 45,
    'image_url'       => '',
    'sizes_available' => [],
  ],
  [
    'id'              => 7,
    'name'            => 'PE Uniform Set',
    'description'     => 'Breathable PE shirt and jogging pants set.',
    'category'        => 'uniform',
    'price'           => 420,
    'markup_price'    => 460,
    'stock_quantity'  => 30,
    'image_url'       => '',
    'sizes_available' => ['S','M','L','XL','XXL'],
  ],
  [
    'id'              => 8,
    'name'            => 'Notebook (College Ruled)',
    'description'     => '80-leaf spiral notebook, college ruled.',
    'category'        => 'school_supply',
    'price'           => 45,
    'markup_price'    => 52,
    'stock_quantity'  => 200,
    'image_url'       => '',
    'sizes_available' => [],
  ],
];

$categoryLabels = [
  'uniform'       => 'Uniforms',
  'id_sling'      => 'ID Slings',
  'booklet'       => 'Booklets',
  'school_supply' => 'School Supplies',
  'merchandise'   => 'Merchandise',
  'other'         => 'Other',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>IGP Product Catalog</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
  <style>
    /* ── Reset & Tokens ─────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --primary:      #1a56db;
      --primary-dk:   #1447b5;
      --primary-lt:   #e8effd;
      --accent:       #f59e0b;
      --surface:      #ffffff;
      --surface-2:    #f4f6fb;
      --border:       #e2e8f0;
      --text:         #1e293b;
      --text-muted:   #64748b;
      --success:      #16a34a;
      --danger:       #dc2626;
      --radius:       12px;
      --radius-sm:    8px;
      --shadow:       0 4px 20px rgba(26,86,219,.08);
      --shadow-hover: 0 8px 32px rgba(26,86,219,.16);
      --font:         'Sora', sans-serif;
      --mono:         'DM Mono', monospace;
    }

    body {
      font-family: var(--font);
      background: var(--surface-2);
      color: var(--text);
      min-height: 100vh;
    }

    /* ── Header ─────────────────────────────────────────────── */
    .page-header {
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      padding: 28px 32px 24px;
      display: flex;
      align-items: flex-end;
      gap: 16px;
    }
    .page-header-icon {
      width: 48px; height: 48px;
      background: var(--primary-lt);
      border-radius: var(--radius-sm);
      display: grid; place-items: center;
      flex-shrink: 0;
    }
    .page-header-icon svg { color: var(--primary); }
    .page-header h1 {
      font-size: 1.5rem; font-weight: 700;
      letter-spacing: -.02em; line-height: 1.1;
    }
    .page-header p { font-size: .875rem; color: var(--text-muted); margin-top: 2px; }

    /* ── Main layout ─────────────────────────────────────────── */
    .main { max-width: 1280px; margin: 0 auto; padding: 28px 24px 60px; }

    /* ── Toolbar ─────────────────────────────────────────────── */
    .toolbar {
      display: flex; gap: 12px; flex-wrap: wrap;
      margin-bottom: 24px;
    }
    .search-wrap {
      position: relative; flex: 1; min-width: 220px;
    }
    .search-wrap svg {
      position: absolute; left: 12px; top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted); pointer-events: none;
    }
    .search-wrap input {
      width: 100%; padding: 10px 14px 10px 38px;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      background: var(--surface);
      font-family: var(--font); font-size: .9rem;
      color: var(--text); outline: none;
      transition: border-color .18s;
    }
    .search-wrap input:focus { border-color: var(--primary); }

    .cat-select {
      padding: 10px 14px;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      background: var(--surface);
      font-family: var(--font); font-size: .9rem;
      color: var(--text); cursor: pointer; outline: none;
      transition: border-color .18s; min-width: 180px;
    }
    .cat-select:focus { border-color: var(--primary); }

    /* ── Cart pill ───────────────────────────────────────────── */
    .cart-pill {
      display: flex; align-items: center; gap: 8px;
      padding: 10px 18px;
      background: var(--primary); color: #fff;
      border: none; border-radius: var(--radius-sm);
      font-family: var(--font); font-size: .9rem; font-weight: 600;
      cursor: pointer; transition: background .18s;
      position: relative;
    }
    .cart-pill:hover { background: var(--primary-dk); }
    .cart-count {
      background: var(--accent); color: #1e293b;
      font-family: var(--mono); font-size: .75rem; font-weight: 500;
      border-radius: 99px; padding: 1px 7px;
      min-width: 22px; text-align: center;
    }

    /* ── Results count ───────────────────────────────────────── */
    .results-label {
      font-size: .82rem; color: var(--text-muted);
      font-family: var(--mono); margin-bottom: 16px;
    }

    /* ── Grid ────────────────────────────────────────────────── */
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 20px;
    }

    /* ── Card ────────────────────────────────────────────────── */
    .card {
      background: var(--surface);
      border-radius: var(--radius);
      border: 1.5px solid var(--border);
      overflow: hidden;
      box-shadow: var(--shadow);
      transition: box-shadow .22s, transform .22s;
      display: flex; flex-direction: column;
    }
    .card:hover {
      box-shadow: var(--shadow-hover);
      transform: translateY(-3px);
    }

    .card-thumb {
      width: 100%; height: 168px;
      background: linear-gradient(135deg, var(--primary-lt) 0%, #dbeafe 100%);
      display: flex; align-items: center; justify-content: center;
      position: relative; overflow: hidden;
    }
    .card-thumb img {
      width: 100%; height: 100%; object-fit: cover;
    }
    .card-thumb-icon { color: #93b4f0; }

    .cat-badge {
      position: absolute; top: 10px; right: 10px;
      background: var(--primary); color: #fff;
      font-size: .7rem; font-weight: 600; letter-spacing: .04em;
      padding: 3px 10px; border-radius: 99px;
      font-family: var(--mono); text-transform: uppercase;
    }

    .card-body { padding: 16px; flex: 1; display: flex; flex-direction: column; }

    .card-name {
      font-size: .95rem; font-weight: 700;
      line-height: 1.2; white-space: nowrap;
      overflow: hidden; text-overflow: ellipsis;
    }
    .card-desc {
      font-size: .78rem; color: var(--text-muted);
      margin-top: 5px; line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .card-meta {
      display: flex; align-items: center;
      justify-content: space-between;
      margin-top: 12px;
    }
    .card-price {
      font-size: 1.2rem; font-weight: 700;
      color: var(--primary); font-family: var(--mono);
    }
    .card-stock {
      font-size: .72rem; color: var(--text-muted);
      font-family: var(--mono);
    }
    .card-stock.out { color: var(--danger); font-weight: 600; }

    /* ── Size buttons ────────────────────────────────────────── */
    .size-row {
      display: flex; flex-wrap: wrap; gap: 6px;
      margin-top: 12px;
    }
    .size-btn {
      padding: 4px 11px; font-size: .72rem;
      border: 1.5px solid var(--border);
      border-radius: 6px; background: var(--surface);
      cursor: pointer; font-family: var(--mono);
      color: var(--text); transition: all .15s;
    }
    .size-btn:hover { border-color: var(--primary); color: var(--primary); }
    .size-btn.active {
      background: var(--primary); color: #fff;
      border-color: var(--primary);
    }

    /* ── Add to cart button ──────────────────────────────────── */
    .add-btn {
      margin-top: auto; padding-top: 12px;
    }
    .add-btn button {
      width: 100%; padding: 9px 0;
      border: none; border-radius: var(--radius-sm);
      background: var(--primary); color: #fff;
      font-family: var(--font); font-size: .88rem; font-weight: 600;
      cursor: pointer; display: flex; align-items: center;
      justify-content: center; gap: 6px;
      transition: background .18s, transform .12s;
    }
    .add-btn button:hover:not(:disabled) {
      background: var(--primary-dk); transform: scale(1.01);
    }
    .add-btn button:disabled {
      background: var(--border); color: var(--text-muted);
      cursor: not-allowed; transform: none;
    }

    /* ── Empty state ─────────────────────────────────────────── */
    .empty {
      text-align: center; padding: 80px 20px;
      color: var(--text-muted);
    }
    .empty svg { margin: 0 auto 16px; display: block; opacity: .3; }
    .empty h3 { font-size: 1.1rem; font-weight: 600; color: var(--text); }
    .empty p  { font-size: .875rem; margin-top: 6px; }

    /* ── Toast ───────────────────────────────────────────────── */
    #toast-container {
      position: fixed; bottom: 24px; right: 24px;
      display: flex; flex-direction: column; gap: 10px;
      z-index: 9999;
    }
    .toast {
      padding: 13px 20px; border-radius: var(--radius-sm);
      font-size: .875rem; font-weight: 500;
      box-shadow: 0 4px 20px rgba(0,0,0,.15);
      animation: slideIn .25s ease;
      display: flex; align-items: center; gap: 10px;
      max-width: 300px;
    }
    .toast.success { background: var(--success); color: #fff; }
    .toast.error   { background: var(--danger);  color: #fff; }
    @keyframes slideIn {
      from { opacity:0; transform: translateX(30px); }
      to   { opacity:1; transform: translateX(0); }
    }

    /* ── Cart drawer ─────────────────────────────────────────── */
    #cart-overlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(15,23,42,.45);
      z-index: 100; backdrop-filter: blur(3px);
    }
    #cart-overlay.open { display: block; }

    #cart-drawer {
      position: fixed; top: 0; right: 0;
      width: min(400px, 100vw); height: 100vh;
      background: var(--surface);
      box-shadow: -8px 0 40px rgba(0,0,0,.12);
      z-index: 101;
      display: flex; flex-direction: column;
      transform: translateX(110%);
      transition: transform .3s cubic-bezier(.4,0,.2,1);
    }
    #cart-drawer.open { transform: translateX(0); }

    .drawer-head {
      padding: 24px 20px 16px;
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .drawer-head h2 { font-size: 1.1rem; font-weight: 700; }
    .drawer-close {
      background: none; border: none; cursor: pointer;
      color: var(--text-muted); padding: 4px;
      border-radius: 6px; transition: background .15s;
    }
    .drawer-close:hover { background: var(--surface-2); }

    .drawer-body {
      flex: 1; overflow-y: auto; padding: 16px 20px;
    }
    .cart-item {
      display: flex; gap: 12px; align-items: center;
      padding: 12px 0; border-bottom: 1px solid var(--border);
    }
    .cart-item-info { flex: 1; min-width: 0; }
    .cart-item-name {
      font-size: .88rem; font-weight: 600;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .cart-item-meta {
      font-size: .75rem; color: var(--text-muted);
      font-family: var(--mono); margin-top: 2px;
    }
    .cart-item-price {
      font-size: .9rem; font-weight: 700;
      color: var(--primary); font-family: var(--mono); white-space: nowrap;
    }
    .cart-qty {
      display: flex; align-items: center; gap: 8px;
    }
    .qty-btn {
      width: 28px; height: 28px; border-radius: 6px;
      border: 1.5px solid var(--border); background: var(--surface);
      cursor: pointer; display: grid; place-items: center;
      font-size: 1rem; transition: all .15s; color: var(--text);
    }
    .qty-btn:hover { border-color: var(--primary); color: var(--primary); }
    .qty-val {
      font-family: var(--mono); font-size: .85rem;
      font-weight: 600; min-width: 18px; text-align: center;
    }

    .drawer-foot {
      padding: 16px 20px 24px;
      border-top: 1px solid var(--border);
    }
    .drawer-total {
      display: flex; justify-content: space-between;
      font-weight: 700; font-size: 1rem; margin-bottom: 14px;
    }
    .drawer-total span:last-child { font-family: var(--mono); color: var(--primary); }

    .checkout-btn {
      width: 100%; padding: 13px;
      background: var(--primary); color: #fff;
      border: none; border-radius: var(--radius-sm);
      font-family: var(--font); font-size: .95rem; font-weight: 700;
      cursor: pointer; transition: background .18s;
    }
    .checkout-btn:hover { background: var(--primary-dk); }

    .cart-empty {
      text-align: center; padding: 60px 0;
      color: var(--text-muted); font-size: .9rem;
    }

    /* ── Responsive ──────────────────────────────────────────── */
    @media (max-width: 600px) {
      .page-header { padding: 20px 16px; }
      .main { padding: 20px 14px 60px; }
      .toolbar { gap: 8px; }
    }
  </style>
</head>
<body>

<!-- ── Page header ───────────────────────────────────────── -->
<header class="page-header">
  <div class="page-header-icon">
    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
    </svg>
  </div>
  <div>
    <h1>Product Catalog</h1>
    <p>Browse and order IGP products</p>
  </div>
</header>

<!-- ── Main ──────────────────────────────────────────────── -->
<main class="main">

  <!-- Toolbar -->
  <div class="toolbar">
    <div class="search-wrap">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      <input id="search" type="text" placeholder="Search products…" oninput="filterProducts()"/>
    </div>

    <select id="category" class="cat-select" onchange="filterProducts()">
      <option value="all">All Categories</option>
      <?php foreach ($categoryLabels as $key => $label): ?>
        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>

    <button class="cart-pill" onclick="openCart()">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/>
      </svg>
      Cart <span class="cart-count" id="cart-count">0</span>
    </button>
  </div>

  <p class="results-label" id="results-label"></p>

  <!-- Product grid (rendered by JS from PHP data) -->
  <div id="grid" class="grid"></div>
  <div id="empty" class="empty" style="display:none">
    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
      <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
    </svg>
    <h3>No products found</h3>
    <p>Try adjusting your search or filter.</p>
  </div>

</main>

<!-- ── Cart drawer ────────────────────────────────────────── -->
<div id="cart-overlay" onclick="closeCart()"></div>
<aside id="cart-drawer">
  <div class="drawer-head">
    <h2>Your Cart</h2>
    <button class="drawer-close" onclick="closeCart()" title="Close">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M18 6 6 18M6 6l12 12"/>
      </svg>
    </button>
  </div>
  <div class="drawer-body" id="cart-body"></div>
  <div class="drawer-foot" id="cart-foot" style="display:none">
    <div class="drawer-total">
      <span>Total</span><span id="cart-total">₱0</span>
    </div>
    <button class="checkout-btn">Proceed to Checkout</button>
  </div>
</aside>

<!-- ── Toast container ────────────────────────────────────── -->
<div id="toast-container"></div>

<!-- ── PHP data → JS ──────────────────────────────────────── -->
<script>
const PRODUCTS = <?= json_encode($products, JSON_UNESCAPED_UNICODE) ?>;
const CATEGORY_LABELS = <?= json_encode($categoryLabels, JSON_UNESCAPED_UNICODE) ?>;

// ── State ────────────────────────────────────────────────── //
let cart = [];           // [{...product, qty, size}]
let selectedSizes = {};  // {product_id: size}

// ── Render grid ──────────────────────────────────────────── //
function filterProducts() {
  const q   = document.getElementById('search').value.toLowerCase();
  const cat = document.getElementById('category').value;

  const list = PRODUCTS.filter(p => {
    const matchSearch = (p.name || '').toLowerCase().includes(q) ||
                        (p.description || '').toLowerCase().includes(q);
    const matchCat = cat === 'all' || p.category === cat;
    return matchSearch && matchCat;
  });

  renderGrid(list);
}

function renderGrid(list) {
  const grid  = document.getElementById('grid');
  const empty = document.getElementById('empty');
  const label = document.getElementById('results-label');

  label.textContent = `${list.length} product${list.length !== 1 ? 's' : ''} found`;

  if (list.length === 0) {
    grid.innerHTML = '';
    empty.style.display = 'block';
    return;
  }
  empty.style.display = 'none';

  grid.innerHTML = list.map(p => {
    const price = p.markup_price || p.price || 0;
    const inStock = p.stock_quantity > 0;
    const catLabel = CATEGORY_LABELS[p.category] || p.category;
    const selSize = selectedSizes[p.id] || '';

    const thumbHTML = p.image_url
      ? `<img src="${escHtml(p.image_url)}" alt="${escHtml(p.name)}" loading="lazy"/>`
      : `<svg class="card-thumb-icon" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
           <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/>
         </svg>`;

    const sizesHTML = (p.sizes_available || []).length
      ? `<div class="size-row">${p.sizes_available.map(s =>
          `<button class="size-btn${selSize === s ? ' active' : ''}"
            onclick="selectSize(${p.id}, '${escHtml(s)}')">${escHtml(s)}</button>`
        ).join('')}</div>`
      : '';

    return `
      <div class="card" id="card-${p.id}">
        <div class="card-thumb">
          ${thumbHTML}
          <span class="cat-badge">${escHtml(catLabel)}</span>
        </div>
        <div class="card-body">
          <div class="card-name" title="${escHtml(p.name)}">${escHtml(p.name)}</div>
          <div class="card-desc">${escHtml(p.description || 'No description')}</div>
          <div class="card-meta">
            <span class="card-price">₱${price.toLocaleString()}</span>
            <span class="card-stock${inStock ? '' : ' out'}">${inStock ? p.stock_quantity + ' in stock' : 'Out of stock'}</span>
          </div>
          ${sizesHTML}
          <div class="add-btn">
            <button onclick="addToCart(${p.id})" ${inStock ? '' : 'disabled'}>
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
              </svg>
              ${inStock ? 'Add to Cart' : 'Out of Stock'}
            </button>
          </div>
        </div>
      </div>`;
  }).join('');
}

// ── Size selection ────────────────────────────────────────── //
function selectSize(productId, size) {
  selectedSizes[productId] = size;
  // Re-render just this card's size buttons
  const card = document.getElementById(`card-${productId}`);
  if (!card) return;
  card.querySelectorAll('.size-btn').forEach(btn => {
    btn.classList.toggle('active', btn.textContent.trim() === size);
  });
}

// ── Add to cart ───────────────────────────────────────────── //
function addToCart(productId) {
  const product = PRODUCTS.find(p => p.id === productId);
  if (!product) return;

  const needsSize = product.category === 'uniform' && (product.sizes_available || []).length > 0;
  const size = selectedSizes[productId] || '';

  if (needsSize && !size) {
    showToast('Please select a size first', 'error');
    return;
  }

  const key = `${productId}_${size}`;
  const existing = cart.find(c => c._key === key);
  if (existing) {
    existing.qty++;
  } else {
    cart.push({
      _key: key,
      id: product.id,
      name: product.name,
      price: product.markup_price || product.price,
      size,
      qty: 1,
      image_url: product.image_url || '',
    });
  }

  updateCartUI();
  showToast('Added to cart!', 'success');
}

// ── Cart UI ───────────────────────────────────────────────── //
function updateCartUI() {
  const totalQty = cart.reduce((s, c) => s + c.qty, 0);
  document.getElementById('cart-count').textContent = totalQty;

  const body = document.getElementById('cart-body');
  const foot = document.getElementById('cart-foot');

  if (cart.length === 0) {
    body.innerHTML = `<div class="cart-empty">Your cart is empty.</div>`;
    foot.style.display = 'none';
    return;
  }

  foot.style.display = 'block';
  let total = 0;
  body.innerHTML = cart.map(item => {
    total += item.price * item.qty;
    return `
      <div class="cart-item">
        <div class="cart-item-info">
          <div class="cart-item-name">${escHtml(item.name)}</div>
          <div class="cart-item-meta">${item.size ? 'Size: ' + escHtml(item.size) + ' · ' : ''}₱${item.price.toLocaleString()} each</div>
        </div>
        <div class="cart-qty">
          <button class="qty-btn" onclick="changeQty('${item._key}', -1)">−</button>
          <span class="qty-val">${item.qty}</span>
          <button class="qty-btn" onclick="changeQty('${item._key}', 1)">+</button>
        </div>
        <span class="cart-item-price">₱${(item.price * item.qty).toLocaleString()}</span>
      </div>`;
  }).join('');

  document.getElementById('cart-total').textContent = '₱' + total.toLocaleString();
}

function changeQty(key, delta) {
  const item = cart.find(c => c._key === key);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) cart = cart.filter(c => c._key !== key);
  updateCartUI();
}

function openCart()  {
  document.getElementById('cart-overlay').classList.add('open');
  document.getElementById('cart-drawer').classList.add('open');
}
function closeCart() {
  document.getElementById('cart-overlay').classList.remove('open');
  document.getElementById('cart-drawer').classList.remove('open');
}

// ── Toast ─────────────────────────────────────────────────── //
function showToast(msg, type = 'success') {
  const container = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
    ${type === 'success'
      ? '<polyline points="20 6 9 17 4 12"/>'
      : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'}
  </svg>${escHtml(msg)}`;
  container.appendChild(t);
  setTimeout(() => t.remove(), 3000);
}

// ── Utility ───────────────────────────────────────────────── //
function escHtml(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Init ──────────────────────────────────────────────────── //
renderGrid(PRODUCTS);
</script>
</body>
</html>