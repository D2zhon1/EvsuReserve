<?php
session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/product_sizes.php';

$ctx        = evsu_student_init($conn);
$first_name = $ctx['first_name'];
$user_name  = $ctx['user_name'];

$category_labels = [
    'uniform'       => 'Uniforms',
    'uniforms'      => 'Uniforms',
    'id_sling'      => 'ID Slings',
    'booklet'       => 'Booklets',
    'school_supply' => 'School Supplies',
    'merchandise'   => 'Merchandise',
    'accessories'   => 'Accessories',
    'general'       => 'General',
    'other'         => 'Other',
];

$products = [];
$res = $conn->query(
    'SELECT id, name, description, category,
            COALESCE(NULLIF(markup_price, 0), unit_price) AS price,
            stock_quantity, size_stock, sizes_available,
            COALESCE(image_url, \'\') AS image_url
     FROM products WHERE is_active = 1 ORDER BY name'
);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $sizeStock = evsu_parse_size_stock($row['size_stock'] ?? null);
        $row['price'] = (float) $row['price'];
        $row['stock_quantity'] = $sizeStock !== []
            ? evsu_size_stock_total($sizeStock)
            : (int) $row['stock_quantity'];
        $row['size_stock'] = $sizeStock;
        $sizes = trim($row['sizes_available'] ?? '');
        $sizesList = $sizes !== '' ? array_map('trim', explode(',', $sizes)) : [];
        if ($sizeStock !== []) {
            $sizesList = array_keys($sizeStock);
        }
        $row['sizes_available'] = $sizesList;
        $products[] = $row;
    }
}

// Active filter from GET
$active_category = $_GET['category'] ?? 'all';
$search_query    = $_GET['search']   ?? '';

// Filter products
$filtered = array_filter($products, function($p) use ($active_category, $search_query) {
    $match_cat    = ($active_category === 'all') || ($p['category'] === $active_category);
    $match_search = empty($search_query) ||
        stripos($p['name'], $search_query) !== false ||
        stripos($p['description'], $search_query) !== false;
    return $match_cat && $match_search;
});

$cart_count = $ctx['cart_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Product Catalog — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/student_dashboard.css"/>
  <link rel="stylesheet" href="../CSS/student_product.css"/>
</head>
<body>

<!-- ══ SIDEBAR ══════════════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-top">
    <div class="sidebar-logo">
      <div class="logo-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
          <path d="M6 12v5c3 3 9 3 12 0v-5"/>
        </svg>
      </div>
      <div>
        <span class="logo-name">EVSU</span>
        <span class="logo-sub">RESERVE</span>
      </div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a href="student_dashboard.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Dashboard
    </a>
    <a href="student_product.php" class="nav-item active">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      Products
    </a>
    <a href="student_orders.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/></svg>
      My Orders
    </a>
    <a href="student_cart.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      Cart
      <?php if ($cart_count > 0): ?>
        <span class="nav-badge"><?= $cart_count ?></span>
      <?php endif; ?>
    </a>
    <a href="student_profile.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profile
    </a>
  </nav>
  <div class="sidebar-bottom">
    <a href="../logout.php" class="nav-item nav-logout">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Sign Out
    </a>
  </div>
</aside>

<!-- ══ MAIN ══════════════════════════════════════════════════════════════ -->
<div class="main-wrap">
  <header class="topbar">
    <button class="menu-btn" onclick="toggleSidebar()" aria-label="Toggle menu">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="topbar-right">
      <a href="student_cart.php" class="topbar-cart">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        <?php if ($cart_count > 0): ?>
          <span class="cart-dot"><?= $cart_count ?></span>
        <?php endif; ?>
      </a>
      <div class="topbar-user">
        <div class="user-avatar"><?= strtoupper(substr($first_name,0,1)) ?></div>
        <span class="user-name"><?= htmlspecialchars($first_name) ?></span>
      </div>
    </div>
  </header>

  <main class="page-content">

    <!-- Page header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Product Catalog</h1>
        <p class="page-sub">Browse and order IGP products</p>
      </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="student_product.php" class="filters-bar" id="filter-form">
      <div class="search-wrap">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          type="text"
          name="search"
          class="search-input"
          placeholder="Search products..."
          value="<?= htmlspecialchars($search_query) ?>"
          oninput="this.form.submit()"
        />
      </div>
      <div class="category-tabs">
        <a href="student_product.php?category=all&search=<?= urlencode($search_query) ?>"
           class="cat-tab <?= $active_category === 'all' ? 'active' : '' ?>">All</a>
        <?php foreach ($category_labels as $key => $label): ?>
          <a href="student_product.php?category=<?= $key ?>&search=<?= urlencode($search_query) ?>"
             class="cat-tab <?= $active_category === $key ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
      </div>
    </form>

    <!-- Result count -->
    <p class="result-count">
      Showing <strong><?= count($filtered) ?></strong> product<?= count($filtered) !== 1 ? 's' : '' ?>
      <?php if ($active_category !== 'all'): ?>
        in <strong><?= htmlspecialchars($category_labels[$active_category] ?? $active_category) ?></strong>
      <?php endif; ?>
    </p>

    <!-- Products grid -->
    <?php if (empty($filtered)): ?>
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="1.5"
             stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <p class="empty-title">No products found</p>
        <p class="empty-sub">Try adjusting your search or filter.</p>
        <a href="student_product.php" class="btn-shop btn-shop-sm">Clear Filters</a>
      </div>

    <?php else: ?>
      <div class="products-grid" id="products-grid">
        <?php foreach ($filtered as $product):
          $cat_label = $category_labels[$product['category']] ?? $product['category'];
          $sizeStock = $product['size_stock'] ?? [];
          $stock_qty = (int) ($product['stock_quantity'] ?? 0);
          $in_stock  = $stock_qty > 0;
          $studentLowThreshold = 5;
          $lowSizes = evsu_low_stock_sizes($sizeStock, $studentLowThreshold);
          $is_low_stock = $stock_qty > 0 && (
              ($sizeStock !== [] && $lowSizes !== [])
              || ($sizeStock === [] && $stock_qty <= $studentLowThreshold)
          );
          $has_sizes = !empty($product['sizes_available']);
          $sizeStockJson = json_encode($sizeStock, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        ?>
        <div class="product-card" data-id="<?= $product['id'] ?>" data-has-sizes="<?= $has_sizes ? '1' : '0' ?>">

          <!-- Image area -->
          <div class="product-img-wrap">
            <?php if (!empty($product['image_url'])): ?>
              <img src="<?= htmlspecialchars($product['image_url']) ?>"
                   alt="<?= htmlspecialchars($product['name']) ?>"
                   class="product-img" />
            <?php else: ?>
              <div class="product-img-placeholder">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.5"
                     stroke-linecap="round" stroke-linejoin="round">
                  <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                  <line x1="3" y1="6" x2="21" y2="6"/>
                  <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
              </div>
            <?php endif; ?>

            <span class="product-badge"><?= htmlspecialchars($cat_label) ?></span>

            <?php if (!$in_stock): ?>
              <div class="out-of-stock-overlay">Out of Stock</div>
            <?php endif; ?>
          </div>

          <!-- Info -->
          <div class="product-body">
            <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
            <p class="product-desc"><?= htmlspecialchars($product['description'] ?: 'No description') ?></p>

            <div class="product-meta">
              <span class="product-price">₱<?= number_format($product['price'], 2) ?></span>
              <span class="product-stock <?= !$in_stock ? 'stock-out' : ($is_low_stock ? 'stock-low' : 'stock-ok') ?>">
                <?php if (!$in_stock): ?>
                  Out of stock
                <?php elseif ($is_low_stock && $lowSizes !== []): ?>
                  Low: <?= htmlspecialchars(evsu_format_low_sizes_text($lowSizes)) ?>
                <?php elseif ($is_low_stock): ?>
                  Low stock (<?= $stock_qty ?> left)
                <?php else: ?>
                  <?= $stock_qty ?> in stock
                <?php endif; ?>
              </span>
            </div>

            <!-- Size selector -->
            <?php if ($has_sizes): ?>
              <div class="size-selector" data-product="<?= $product['id'] ?>">
                <?php foreach ($product['sizes_available'] as $size):
                  $szKey = strtoupper(trim($size));
                  $szQty = $sizeStock !== [] ? (int) ($sizeStock[$szKey] ?? 0) : $stock_qty;
                  $szOos = $szQty <= 0;
                  $szLow = !$szOos && $szQty <= $studentLowThreshold;
                ?>
                  <button type="button"
                          class="size-btn <?= $szOos ? 'size-oos' : ($szLow ? 'size-low' : '') ?>"
                          title="<?= $szLow ? 'Low stock' : ($szOos ? 'Out of stock' : '') ?>"
                          data-product="<?= $product['id'] ?>"
                          data-size="<?= htmlspecialchars($szKey) ?>"
                          data-stock="<?= $szQty ?>"
                          <?= $szOos ? 'disabled' : '' ?>
                          onclick="selectSize(this)">
                    <span class="size-label"><?= htmlspecialchars($szKey) ?></span>
                    <?php if ($sizeStock !== []): ?>
                      <span class="size-stock-qty"><?= $szQty ?></span>
                    <?php endif; ?>
                  </button>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <!-- Add to Cart -->
            <button
              class="btn-add-cart <?= !$in_stock ? 'btn-disabled' : '' ?>"
              <?= !$in_stock ? 'disabled' : '' ?>
              data-product-name="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
              data-price="<?= (float) $product['price'] ?>"
              data-size-stock="<?= $sizeStockJson ?>"
              onclick="addToCart(this)"
            >
              <?php if ($in_stock): ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Add to Cart
              <?php else: ?>
                Out of Stock
              <?php endif; ?>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </main>
</div>

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Toast notification -->
<div class="toast" id="toast"></div>

<script src="../JS/student_dashboard.js"></script>
<script src="../JS/student_product.js"></script>
</body>
</html>