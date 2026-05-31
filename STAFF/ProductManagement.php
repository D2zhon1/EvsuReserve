<?php
session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/product_sizes.php';

$user_name  = $_SESSION['user_name'] ?? 'Admin User';
$first_name = explode(' ', $user_name)[0];
session_write_close();

$products = [];
$res = $conn->query(
    'SELECT id, sku, name, description, category, unit_price, markup_price, stock_quantity, size_stock, sizes_available, image_url, is_active
     FROM products
     ORDER BY created_at DESC, id DESC'
);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $sizeStock = evsu_parse_size_stock($row['size_stock'] ?? null);
        $sizes = array_filter(array_map('trim', explode(',', (string) ($row['sizes_available'] ?? ''))));
        if ($sizeStock !== []) {
            $sizes = array_keys($sizeStock);
        }
        $row['price'] = (float) ($row['unit_price'] ?? 0);
        $row['markup_price'] = isset($row['markup_price']) ? (float) $row['markup_price'] : (float) $row['price'];
        $row['stock_quantity'] = $sizeStock !== []
            ? evsu_size_stock_total($sizeStock)
            : (int) ($row['stock_quantity'] ?? 0);
        $row['size_stock'] = $sizeStock;
        $row['is_active'] = !empty($row['is_active']);
        $row['sizes_available'] = array_values($sizes);
        unset($row['unit_price']);
        $products[] = $row;
    }
}

$categories = [
    'uniform'       => 'Uniform',
    'id_sling'      => 'ID Sling',
    'booklet'       => 'Booklet',
    'school_supply' => 'School Supply',
    'merchandise'   => 'Merchandise',
    'other'         => 'Other',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Product Management — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/student_dashboard.css"/>
  <link rel="stylesheet" href="/CSS/student_dashboard.css"/>
  <link rel="stylesheet" href="../CSS/ProductManagement.css"/>
</head>
<body>

<!-- ══ SIDEBAR ══════════════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-top">
    <div class="sidebar-logo">
      <div class="logo-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
          <path d="M6 12v5c3 3 9 3 12 0v-5"/>
        </svg>
      </div>
      <div>
        <span class="logo-name">EVSU</span>
        <span class="logo-sub">RESERVE</span>
      </div>
    </div>
    <div class="staff-badge">STAFF</div>
  </div>

  <nav class="sidebar-nav">
    <a href="StaffDashboard.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Dashboard
    </a>
    <a href="ProductManagement.php" class="nav-item active">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
        <line x1="3" y1="6" x2="21" y2="6"/>
        <path d="M16 10a4 4 0 0 1-8 0"/>
      </svg>
      Products
    </a>
    <a href="OrderManagement.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
        <path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/>
      </svg>
      Orders
    </a>
    <a href="InventoryManagement.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 16V8a2 2 0 0 0-1-1.73L13 2.27a2 2 0 0 0-2 0L4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
        <line x1="12" y1="22.08" x2="12" y2="12"/>
      </svg>
      Inventory
    </a>
  </nav>

  <div class="sidebar-bottom">
    <a href="../login_page.php" class="nav-item nav-logout">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Sign Out
    </a>
  </div>
</aside>

<!-- ══ MAIN ══════════════════════════════════════════════════════════════ -->
<div class="main-wrap">

  <!-- Topbar -->
  <header class="topbar">
    <button class="menu-btn" onclick="toggleSidebar()" aria-label="Toggle menu">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="6"  x2="21" y2="6"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
    <div class="topbar-right">
      <div class="topbar-user">
        <div class="user-avatar"><?= strtoupper(substr($first_name, 0, 1)) ?></div>
        <span class="user-name"><?= htmlspecialchars($first_name) ?></span>
      </div>
    </div>
  </header>

  <!-- Page content -->
  <main class="page-content">

    <!-- Page header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Product Management</h1>
        <p class="page-sub">Manage and View Products
          
        </p>
      </div>
      <button class="btn-shop" onclick="openModal()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add Product
      </button>
    </div>

    <!-- Search bar -->
    <div class="search-wrap">
      <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <input type="text" id="search-input" class="search-input" placeholder="Search products by name or category…" oninput="filterProducts()"/>
    </div>

    <!-- Products table card -->
    <div class="orders-card">
      <div class="orders-card-header">
        <h2 class="orders-title">All Products</h2>
        <span class="product-count" id="product-count"><?= count($products) ?> products</span>
      </div>

      <?php if (empty($products)): ?>
        <div class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
          <p class="empty-title">No products yet</p>
          <p class="empty-sub">Add your first product to get started.</p>
          <button class="btn-shop btn-shop-sm" onclick="openModal()">Add Product</button>
        </div>
      <?php else: ?>
        <div class="orders-table-wrap">
          <table class="orders-table" id="products-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Base Price</th>
                <th>Markup</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p):
                $cat_label = $categories[$p['category']] ?? ucfirst($p['category']);
                $low_stock  = $p['stock_quantity'] < 10;
              ?>
              <tr data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>"
                  data-category="<?= strtolower(htmlspecialchars($p['category'])) ?>">
                <!-- Product -->
                <td>
                  <div class="product-cell">
                    <div class="product-thumb">
                      <?php if (!empty($p['image_url'])): ?>
                        <img src="<?= htmlspecialchars($p['image_url']) ?>" alt=""/>
                      <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                          <line x1="3" y1="6" x2="21" y2="6"/>
                          <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                      <?php endif; ?>
                    </div>
                    <div>
                      <p class="product-name"><?= htmlspecialchars($p['name']) ?></p>
                      <p class="text-muted" style="font-size:.78rem;"><?= htmlspecialchars($p['sku'] ?: '—') ?></p>
                    </div>
                  </div>
                </td>
                <!-- Category -->
                <td class="text-muted" style="font-size:.88rem;"><?= htmlspecialchars($cat_label) ?></td>
                <!-- Base price -->
                <td style="font-size:.88rem;">₱<?= number_format($p['price'], 2) ?></td>
                <!-- Markup -->
                <td style="font-size:.88rem; font-weight:600;">₱<?= number_format($p['markup_price'], 2) ?></td>
                <!-- Stock -->
                <td>
                  <?php if (!empty($p['size_stock'])): ?>
                    <div class="size-stock-list">
                      <?php foreach ($p['size_stock'] as $sz => $sq): ?>
                        <span class="size-stock-chip <?= $sq < 10 ? 'stock-low' : '' ?>">
                          <?= htmlspecialchars($sz) ?>: <?= (int) $sq ?>
                        </span>
                      <?php endforeach; ?>
                    </div>
                    <span class="stock-total-hint">Total: <?= (int) $p['stock_quantity'] ?></span>
                  <?php else: ?>
                    <span class="stock-val <?= $low_stock ? 'stock-low' : '' ?>">
                      <?= $p['stock_quantity'] ?>
                    </span>
                  <?php endif; ?>
                </td>
                <!-- Status -->
                <td>
                  <span class="badge <?= $p['is_active'] ? 'badge-green' : 'badge-gray' ?>">
                    <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <!-- Actions -->
                <td>
                  <div class="action-btns">
                    <button class="icon-btn icon-btn-edit"
                            onclick="openEdit(<?= htmlspecialchars(json_encode($p)) ?>)"
                            title="Edit">
                      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                    </button>
                    <button class="icon-btn icon-btn-delete"
                            onclick="confirmDelete(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>')"
                            title="Delete">
                      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6M14 11v6"/>
                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- ══ MODAL ══════════════════════════════════════════════════════════════ -->
<div class="modal-backdrop" id="modal-backdrop" onclick="handleBackdropClick(event)">
  <div class="modal" id="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">

    <div class="modal-header">
      <h2 class="modal-title" id="modal-title">Add New Product</h2>
      <button class="modal-close" onclick="closeModal()" aria-label="Close">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    <form class="modal-body" id="product-form" onsubmit="handleSubmit(event)">
      <input type="hidden" id="form-id" name="id"/>

      <!-- Name -->
      <div class="form-group">
        <label class="form-label" for="form-name">Product Name <span class="required">*</span></label>
        <input type="text" id="form-name" name="name" class="form-input" required placeholder="e.g. EVSU PE Uniform"/>
      </div>

      <!-- Description -->
      <div class="form-group">
        <label class="form-label" for="form-desc">Description</label>
        <textarea id="form-desc" name="description" class="form-input form-textarea" rows="2" placeholder="Brief product description…"></textarea>
      </div>

      <!-- Category + SKU -->
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="form-category">Category</label>
          <select id="form-category" name="category" class="form-input form-select">
            <?php foreach ($categories as $val => $label): ?>
              <option value="<?= $val ?>"><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="form-sku">SKU</label>
          <input type="text" id="form-sku" name="sku" class="form-input" placeholder="e.g. UNI-PE-001"/>
        </div>
      </div>

      <!-- Prices + Stock -->
      <div class="form-row form-row-3">
        <div class="form-group">
          <label class="form-label" for="form-price">Base Price (₱) <span class="required">*</span></label>
          <input type="number" id="form-price" name="price" class="form-input" required min="0" step="0.01" placeholder="0.00"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="form-markup">Markup Price (₱)</label>
          <input type="number" id="form-markup" name="markup_price" class="form-input" min="0" step="0.01" placeholder="0.00"/>
        </div>
        <div class="form-group" id="general-stock-wrap">
          <label class="form-label" for="form-stock">Stock Qty</label>
          <input type="number" id="form-stock" name="stock_quantity" class="form-input" min="0" placeholder="0"/>
        </div>
      </div>

      <!-- Image URL -->
      <div class="form-group">
        <label class="form-label" for="form-image">Image URL</label>
        <input type="url" id="form-image" name="image_url" class="form-input" placeholder="https://…"/>
      </div>

      <!-- Sizes + per-size stock -->
      <div class="form-group">
        <label class="form-label">Sizes &amp; stock (uniforms)</label>
        <p class="field-hint">Add S, M, L, XL and set how many pieces are in stock for each size.</p>
        <div class="size-preset-row">
          <?php foreach (['S', 'M', 'L', 'XL'] as $preset): ?>
            <button type="button" class="btn-outline btn-size-preset" onclick="addSizePreset('<?= $preset ?>')"><?= $preset ?></button>
          <?php endforeach; ?>
        </div>
        <div class="size-input-row">
          <input type="text" id="size-input" class="form-input" placeholder="Other size (e.g. 2XL)"
                 onkeydown="if(event.key==='Enter'){event.preventDefault();addSize();}"/>
          <button type="button" class="btn-outline" onclick="addSize()">Add Size</button>
        </div>
        <div class="size-stock-table-wrap" id="size-stock-table-wrap" style="display:none;">
          <table class="size-stock-table">
            <thead>
              <tr>
                <th>Size</th>
                <th>Stock Qty</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="size-stock-rows"></tbody>
          </table>
          <p class="size-stock-total">Total stock: <strong id="size-stock-total">0</strong></p>
        </div>
        <input type="hidden" id="form-sizes" name="sizes_available"/>
      </div>

      <!-- Active toggle -->
      <div class="form-group">
        <label class="toggle-label">
          <div class="toggle-wrap">
            <input type="checkbox" id="form-active" name="is_active" checked/>
            <span class="toggle-track">
              <span class="toggle-thumb"></span>
            </span>
          </div>
          <span class="form-label" style="margin:0;">Active</span>
        </label>
      </div>

    </form>

    <div class="modal-footer">
      <button type="button" class="btn-outline" onclick="closeModal()">Cancel</button>
      <button type="submit" form="product-form" class="btn-shop" id="submit-btn">Create Product</button>
    </div>

  </div>
</div>

<!-- ══ DELETE CONFIRM ═════════════════════════════════════════════════════ -->
<div class="modal-backdrop" id="delete-backdrop" onclick="handleDeleteBackdropClick(event)">
  <div class="modal modal-sm" id="delete-modal">
    <div class="modal-header">
      <h2 class="modal-title">Delete Product</h2>
      <button class="modal-close" onclick="closeDelete()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <div class="modal-body">
      <p class="delete-msg">Are you sure you want to delete <strong id="delete-name"></strong>? This action cannot be undone.</p>
    </div>
    <div class="modal-footer">
      <button class="btn-outline" onclick="closeDelete()">Cancel</button>
      <button class="btn-danger" id="confirm-delete-btn" onclick="executeDelete()">Delete</button>
    </div>
  </div>
</div>

<!-- ══ TOAST ══════════════════════════════════════════════════════════════ -->
<div class="toast-container" id="toast-container"></div>

<script src="/JS/student_dashboard.js"></script>
<script src="/JS/ProductManagement.js"></script>
</body>
</html>