<?php
session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/product_sizes.php';

$user_name  = $_SESSION['user_name'] ?? 'Staff User';
$first_name = explode(' ', $user_name)[0];

$products = [];
$res = $conn->query(
    'SELECT id, name, category, unit_price AS price,
            COALESCE(markup_price, unit_price * 1.2) AS markup_price,
            stock_quantity, size_stock, is_active
     FROM products ORDER BY name'
);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $sizeStock = evsu_parse_size_stock($row['size_stock'] ?? null);
        $row['price'] = (float) $row['price'];
        $row['markup_price'] = (float) $row['markup_price'];
        $row['stock_quantity'] = $sizeStock !== []
            ? evsu_size_stock_total($sizeStock)
            : (int) $row['stock_quantity'];
        $row['size_stock'] = $sizeStock;
        $row['low_sizes'] = evsu_low_stock_sizes($sizeStock);
        $row['is_active'] = (bool) $row['is_active'];
        $products[] = $row;
    }
}

$orders = [];
$res = $conn->query(
    'SELECT o.order_number AS id, u.full_name AS customer_name, o.status, o.payment_status,
            o.total_amount, DATE(o.created_at) AS date
     FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC'
);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $row['total_amount'] = (float) $row['total_amount'];
        $orders[] = $row;
    }
}

// ── Computed values (mirrors React logic) ─────────────────────────────────
$low_stock = array_values(array_filter(
    $products,
    static fn($p) => evsu_product_needs_low_stock_alert(
        (int) ($p['stock_quantity'] ?? 0),
        $p['size_stock'] ?? [],
        evsu_low_stock_threshold()
    )
));

$active_orders = array_filter($orders, fn($o) => !in_array($o['status'], ['completed', 'cancelled']));
$active_orders = array_values($active_orders);

$total_revenue = array_reduce($orders, function($sum, $o) {
    if (in_array($o['payment_status'], ['verified', 'paid'])) {
        $sum += $o['total_amount'] ?? 0;
    }
    return $sum;
}, 0);

$recent_orders = array_slice($orders, 0, 6);

$pending_cash_orders = [];
$res = $conn->query(
    "SELECT o.id, o.order_number, u.full_name AS customer_name, o.total_amount,
            DATE(o.created_at) AS created_date, p.proof_url
     FROM orders o
     JOIN users u ON u.id = o.user_id
     JOIN payments p ON p.order_id = o.id AND p.method = 'Cash' AND p.status = 'pending'
     WHERE o.status = 'pending'
     ORDER BY o.created_at DESC
     LIMIT 10"
);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $row['total_amount'] = (float) $row['total_amount'];
        $pending_cash_orders[] = $row;
    }
}

// ── Status badge config ───────────────────────────────────────────────────
$status_config = [
    'completed'  => ['label' => 'Completed',  'class' => 'badge-green'],
    'pending'    => ['label' => 'Pending',     'class' => 'badge-orange'],
    'processing' => ['label' => 'Processing',  'class' => 'badge-blue'],
    'cancelled'  => ['label' => 'Cancelled',   'class' => 'badge-red'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Staff Dashboard — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/student_dashboard.css"/>
  <link rel="stylesheet" href="../CSS/StaffDashboard.css"/>
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
    <a href="StaffDashboard.php" class="nav-item active">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Dashboard
    </a>
    <a href="ProductManagement.php" class="nav-item">
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
        <h1 class="page-title">Staff Dashboard</h1>
        <p class="page-sub">Welcome, <?= htmlspecialchars($user_name) ?></p>
      </div>
    </div>

    <!-- Stats grid -->
    <div class="stats-grid">

      <div class="stat-card stat-maroon">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Total Products</span>
          <span class="stat-value"><?= count($products) ?></span>
        </div>
      </div>

      <div class="stat-card stat-blue">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="8" y="2" width="8" height="4" rx="1"/>
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
            <path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Active Orders</span>
          <span class="stat-value"><?= count($active_orders) ?></span>
        </div>
      </div>

      <div class="stat-card stat-orange">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/>
            <line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Low Stock Items</span>
          <span class="stat-value"><?= count($low_stock) ?></span>
        </div>
      </div>

      <div class="stat-card stat-green">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Revenue</span>
          <span class="stat-value stat-value-sm">₱<?= number_format($total_revenue, 0) ?></span>
        </div>
      </div>

    </div><!-- /stats-grid -->

    <?php if (!empty($pending_cash_orders)): ?>
    <div class="orders-card" style="margin-bottom:1.25rem;">
      <div class="orders-card-header orders-card-header-warn">
        <h2 class="orders-title"><span class="warn-dot"></span> Cash Orders — Awaiting Confirmation</h2>
        <a href="OrderManagement.php" class="view-all-link">View all</a>
      </div>
      <div class="panel-list">
        <?php foreach ($pending_cash_orders as $pco): ?>
        <div class="panel-row">
          <div class="panel-row-left">
            <p class="panel-row-title"><?= htmlspecialchars($pco['order_number']) ?></p>
            <p class="panel-row-sub"><?= htmlspecialchars($pco['customer_name']) ?> · <?= htmlspecialchars($pco['created_date']) ?></p>
          </div>
          <div class="panel-row-right" style="gap:.5rem;">
            <span class="panel-row-amount">₱<?= number_format($pco['total_amount'], 2) ?></span>
            <button type="button" class="btn-shop btn-shop-sm"
                    onclick="staffConfirmOrder(<?= (int) $pco['id'] ?>, this)">Confirm</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Two-column cards -->
    <div class="dual-grid">

      <!-- Recent Orders card -->
      <div class="orders-card">
        <div class="orders-card-header">
          <h2 class="orders-title">Recent Orders</h2>
        <a href="OrderManagement.php" class="view-all-link">View all</a>
        <?php if (empty($recent_orders)): ?>
          <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="8" y="2" width="8" height="4" rx="1"/>
              <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
            </svg>
            <p class="empty-title">No orders yet</p>
            <p class="empty-sub">Orders will appear here once students start reserving.</p>
          </div>
        <?php else: ?>
          <div class="panel-list">
            <?php foreach ($recent_orders as $order):
              $sc = $status_config[$order['status']] ?? ['label' => ucfirst($order['status']), 'class' => 'badge-gray'];
            ?>
            <div class="panel-row">
              <div class="panel-row-left">
                <p class="panel-row-title"><?= htmlspecialchars($order['id']) ?></p>
                <p class="panel-row-sub"><?= htmlspecialchars($order['customer_name']) ?></p>
              </div>
              <div class="panel-row-right">
                <span class="badge <?= $sc['class'] ?>"><?= $sc['label'] ?></span>
                <span class="panel-row-amount">₱<?= number_format($order['total_amount'], 2) ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Low Stock Alert card -->
      <div class="orders-card">
        <div class="orders-card-header orders-card-header-warn">
          <h2 class="orders-title">
            <?php if (count($low_stock) > 0): ?>
              <span class="warn-dot"></span>
            <?php endif; ?>
            Low Stock Alerts
          </h2>
          <a href="inventory.php" class="view-all-link">Manage</a>
        </div>

        <?php if (empty($low_stock)): ?>
          <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
              <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <p class="empty-title">All stocked up!</p>
            <p class="empty-sub">All products are well-stocked.</p>
          </div>
        <?php else: ?>
          <div class="panel-list">
            <?php foreach ($low_stock as $product):
              $lowSizes = $product['low_sizes'] ?? [];
              $sizeStock = $product['size_stock'] ?? [];
              if ($lowSizes === [] && $sizeStock === []) {
                  $lowSizes = ['Total' => (int) ($product['stock_quantity'] ?? 0)];
              }
              $minQty = $lowSizes !== [] ? min($lowSizes) : (int) ($product['stock_quantity'] ?? 0);
              $critical = $minQty <= 3;
              $cat_label = ucwords(str_replace('_', ' ', $product['category']));
            ?>
            <div class="panel-row">
              <div class="panel-row-left">
                <p class="panel-row-title"><?= htmlspecialchars($product['name']) ?></p>
                <p class="panel-row-sub"><?= htmlspecialchars($cat_label) ?></p>
                <?php if ($sizeStock !== [] && $lowSizes !== []): ?>
                  <div class="panel-low-sizes">
                    <?php foreach ($lowSizes as $sz => $sq): ?>
                      <span class="panel-size-chip <?= $sq <= 3 ? 'stock-critical' : 'stock-warning' ?>">
                        <?= htmlspecialchars($sz) ?>: <?= (int) $sq ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
              <div class="panel-row-right">
                <?php if ($sizeStock !== [] && $lowSizes !== []): ?>
                  <span class="stock-alert <?= $critical ? 'stock-critical' : 'stock-warning' ?>">Low sizes</span>
                <?php else: ?>
                  <span class="stock-alert <?= $critical ? 'stock-critical' : 'stock-warning' ?>">
                    <?= (int) ($product['stock_quantity'] ?? 0) ?> left
                  </span>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /dual-grid -->

  </main>
</div>

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<script src="/JS/student_dashboard.js"></script>
<script src="/JS/StaffDashboard.js"></script>
<script>
function staffConfirmOrder(orderId, btn) {
  if (!confirm('Confirm this cash order? Stock will be reserved and the student can pay at the cashier.')) return;
  btn.disabled = true;
  btn.textContent = 'Confirming…';
  fetch('staff_confirm_order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: orderId }),
  })
    .then(r => r.json())
    .then(data => {
      if (data.success) location.reload();
      else {
        alert(data.message || 'Could not confirm order.');
        btn.disabled = false;
        btn.textContent = 'Confirm';
      }
    })
    .catch(() => {
      alert('Network error.');
      btn.disabled = false;
      btn.textContent = 'Confirm';
    });
}
</script>
</body>
</html>