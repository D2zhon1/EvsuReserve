<?php
session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/product_sizes.php';

$lowStockThreshold = evsu_low_stock_threshold();

$user_name  = $_SESSION['user_name'] ?? 'Staff User';
$first_name = explode(' ', $user_name)[0];
$staff_role = $_SESSION['staff_role'] ?? 'Staff';
session_write_close();

$stats = $conn->query(
    "SELECT
        COUNT(*) AS total_orders,
        SUM(status = 'pending') AS pending_orders,
        SUM(status = 'processing') AS processing_orders,
        SUM(status = 'completed') AS completed_orders,
        SUM(CASE WHEN payment_status IN ('verified','paid') THEN total_amount ELSE 0 END) AS total_revenue
     FROM orders"
)->fetch_assoc();

$total_orders       = (int) ($stats['total_orders'] ?? 0);
$pending_orders     = (int) ($stats['pending_orders'] ?? 0);
$processing_orders  = (int) ($stats['processing_orders'] ?? 0);
$completed_orders   = (int) ($stats['completed_orders'] ?? 0);
$total_revenue      = (float) ($stats['total_revenue'] ?? 0);

$total_students = (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'student'")->fetch_assoc()['c'];

$low_stock_items = 0;

$recent_orders = [];
$res = $conn->query(
    "SELECT o.order_number AS id, u.full_name AS student, o.status,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS items,
            DATE(o.created_at) AS date, o.total_amount AS total, o.payment_status AS payment
     FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC LIMIT 7"
);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $row['total'] = (float) $row['total'];
        $row['items'] = (int) $row['items'];
        $recent_orders[] = $row;
    }
}

$low_stock_products = [];
$res = $conn->query(
    "SELECT id, name, COALESCE(sku, CONCAT('SKU-', id)) AS sku, stock_quantity, size_stock
     FROM products WHERE is_active = 1 ORDER BY name"
);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $sizeStock = evsu_parse_size_stock($row['size_stock'] ?? null);
        $stock = $sizeStock !== []
            ? evsu_size_stock_total($sizeStock)
            : (int) ($row['stock_quantity'] ?? 0);
        $lowSizes = evsu_low_stock_sizes($sizeStock, $lowStockThreshold);

        if (!evsu_product_needs_low_stock_alert($stock, $sizeStock, $lowStockThreshold)) {
            continue;
        }

        $low_stock_items++;
        $low_stock_products[] = [
            'name'       => $row['name'],
            'sku'        => $row['sku'],
            'stock'      => $stock,
            'size_stock' => $sizeStock,
            'low_sizes'  => $lowSizes,
            'threshold'  => $lowStockThreshold,
            'sort_qty'   => $lowSizes !== [] ? min($lowSizes) : $stock,
        ];
    }
    usort($low_stock_products, static fn($a, $b) => $a['sort_qty'] <=> $b['sort_qty']);
    $low_stock_products = array_slice($low_stock_products, 0, 10);
}

$status_config = [
    'completed'  => ['label' => 'Completed',  'class' => 'badge-green'],
    'pending'    => ['label' => 'Pending',     'class' => 'badge-orange'],
    'processing' => ['label' => 'Processing',  'class' => 'badge-blue'],
    'cancelled'  => ['label' => 'Cancelled',   'class' => 'badge-red'],
];

$payment_config = [
    'verified' => ['label' => 'Verified',  'class' => 'badge-green'],
    'paid'     => ['label' => 'Paid',      'class' => 'badge-blue'],
    'pending'  => ['label' => 'Pending',   'class' => 'badge-orange'],
    'refunded' => ['label' => 'Refunded',  'class' => 'badge-gray'],
];
// ──────────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Staff Dashboard — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/InventoryManagement.css"/>
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

  <!-- Top bar -->
  <header class="topbar">
    <button class="menu-btn" onclick="toggleSidebar()" aria-label="Toggle menu">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="6"  x2="21" y2="6"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>

    <!-- Quick actions -->
    <div class="topbar-actions">
      <a href="OrderManagement.php?filter=pending" class="quick-action-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
        <?= $pending_orders ?> Pending
      </a>
    </div>

    <div class="topbar-right">
      <div class="topbar-user">
        <div class="user-avatar"><?= strtoupper(substr($first_name, 0, 1)) ?></div>
        <div class="user-info">
          <span class="user-name"><?= htmlspecialchars($first_name) ?></span>
        </div>
      </div>
    </div>
  </header>

  <!-- Page content -->
  <main class="page-content">

    <!-- Page header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Good day, <?= htmlspecialchars($first_name) ?>!</h1>
        <p class="page-sub">Here's what's happening in EVSU Reserve today</p>
      </div>
      <div class="page-header-actions">
        <a href="ProductManagement.php" class="btn-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Add Product
        </a>
        <a href="staff_reports.php" class="btn-outline">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"/>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
            <rect x="6" y="14" width="12" height="8"/>
          </svg>
          Export Report
        </a>
      </div>
    </div>

    <!-- Stats grid -->
    <div class="stats-grid">

      <div class="stat-card stat-maroon">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <rect x="8" y="2" width="8" height="4" rx="1"/>
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
            <path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Total Orders</span>
          <span class="stat-value"><?= $total_orders ?></span>
        </div>
      </div>

      <div class="stat-card stat-orange">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Pending Orders</span>
          <span class="stat-value"><?= $pending_orders ?></span>
        </div>
      </div>

      <div class="stat-card stat-green">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="1" x2="12" y2="23"/>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Total Revenue</span>
          <span class="stat-value">₱<?= number_format($total_revenue / 1000, 1) ?>k</span>
        </div>
      </div>

      <div class="stat-card stat-blue">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Total Students</span>
          <span class="stat-value"><?= $total_students ?></span>
        </div>
      </div>

    </div><!-- /stats-grid -->

    <!-- Two-column layout: Orders + Low Stock -->
    <div class="dashboard-grid">

      <!-- Recent Orders -->
      <div class="orders-card">
        <div class="orders-card-header">
          <h2 class="orders-title">Recent Orders</h2>
          <a href="OrderManagement.php" class="view-all-link">View all</a>
        </div>

        <?php if (empty($recent_orders)): ?>
          <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="1.5"
                 stroke-linecap="round" stroke-linejoin="round">
              <rect x="8" y="2" width="8" height="4" rx="1"/>
              <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
            </svg>
            <p class="empty-title">No orders yet</p>
            <p class="empty-sub">Orders placed by students will appear here.</p>
          </div>
        <?php else: ?>
          <div class="orders-table-wrap">
            <table class="orders-table">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Student</th>
                  <th>Status</th>
                  <th>Payment</th>
                  <th class="text-right">Total</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($recent_orders, 0, 7) as $order):
                  $sc = $status_config[$order['status']]  ?? ['label' => ucfirst($order['status']),  'class' => 'badge-gray'];
                  $pc = $payment_config[$order['payment']] ?? ['label' => ucfirst($order['payment']), 'class' => 'badge-gray'];
                  $date_fmt = date('M j', strtotime($order['date']));
                ?>
                <tr>
                  <td class="order-num"><?= htmlspecialchars($order['id']) ?></td>
                  <td class="order-student">
                    <div class="student-avatar"><?= strtoupper(substr($order['student'], 0, 1)) ?></div>
                    <span><?= htmlspecialchars($order['student']) ?></span>
                  </td>
                  <td><span class="badge <?= $sc['class'] ?>"><?= $sc['label'] ?></span></td>
                  <td><span class="badge <?= $pc['class'] ?>"><?= $pc['label'] ?></span></td>
                  <td class="order-total text-right">₱<?= number_format($order['total'], 2) ?></td>
                  <td class="text-right">
                    <a href="OrderManagement.php?id=<?= urlencode($order['id']) ?>" class="action-link">View</a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div><!-- /orders-card -->

      <!-- Right column -->
      <div class="right-column">

        <!-- Order Status Summary -->
        <div class="summary-card">
          <div class="orders-card-header">
            <h2 class="orders-title">Order Overview</h2>
            <span class="date-label"><?= date('M Y') ?></span>
          </div>
          <div class="order-stats">
            <div class="order-stat-row">
              <span class="ostat-dot dot-orange"></span>
              <span class="ostat-label">Pending</span>
              <div class="ostat-bar-wrap">
                <div class="ostat-bar bar-orange" style="width:<?= ($total_orders > 0 ? round($pending_orders / $total_orders * 100) : 0) ?>%"></div>
              </div>
              <span class="ostat-count"><?= $pending_orders ?></span>
            </div>
            <div class="order-stat-row">
              <span class="ostat-dot dot-blue"></span>
              <span class="ostat-label">Processing</span>
              <div class="ostat-bar-wrap">
                <div class="ostat-bar bar-blue" style="width:<?= ($total_orders > 0 ? round($processing_orders / $total_orders * 100) : 0) ?>%"></div>
              </div>
              <span class="ostat-count"><?= $processing_orders ?></span>
            </div>
            <div class="order-stat-row">
              <span class="ostat-dot dot-green"></span>
              <span class="ostat-label">Completed</span>
              <div class="ostat-bar-wrap">
                <div class="ostat-bar bar-green" style="width:<?= ($total_orders > 0 ? round($completed_orders / $total_orders * 100) : 0) ?>%"></div>
              </div>
              <span class="ostat-count"><?= $completed_orders ?></span>
            </div>
          </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="summary-card">
          <div class="orders-card-header">
            <h2 class="orders-title">
              Low Stock Alert
              <?php if ($low_stock_items > 0): ?>
                <span class="title-badge"><?= $low_stock_items ?></span>
              <?php endif; ?>
            </h2>
            <a href="ProductManagement.php?filter=low_stock" class="view-all-link">Manage</a>
          </div>
          <?php if (empty($low_stock_products)): ?>
            <div class="empty-state-sm">
              <p>All products are sufficiently stocked.</p>
            </div>
          <?php else: ?>
            <ul class="stock-list">
              <?php foreach ($low_stock_products as $product):
                $lowSizes = $product['low_sizes'] ?? [];
                $hasSizeLow = $lowSizes !== [];
                $minQty = $hasSizeLow ? min($lowSizes) : (int) $product['stock'];
              ?>
                <li class="stock-item">
                  <div class="stock-info">
                    <span class="stock-name"><?= htmlspecialchars($product['name']) ?></span>
                    <span class="stock-sku"><?= htmlspecialchars($product['sku']) ?></span>
                    <?php if ($hasSizeLow): ?>
                      <div class="stock-size-alerts">
                        <?php foreach ($lowSizes as $sz => $sq): ?>
                          <span class="stock-size-chip <?= $sq === 0 ? 'qty-zero' : ($sq <= 3 ? 'qty-critical' : 'qty-low') ?>">
                            <?= htmlspecialchars($sz) ?>: <?= (int) $sq ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="stock-qty <?= $minQty === 0 ? 'qty-zero' : 'qty-low' ?>">
                    <?php if ($hasSizeLow): ?>
                      <span class="badge badge-orange">Low sizes</span>
                    <?php elseif ($product['stock'] === 0): ?>
                      <span class="badge badge-red">Out of Stock</span>
                    <?php else: ?>
                      <span class="badge badge-orange"><?= (int) $product['stock'] ?> left</span>
                    <?php endif; ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>

      </div><!-- /right-column -->
    </div><!-- /dashboard-grid -->

  </main>
</div><!-- /main-wrap -->

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<script src="/JS/InventoryManagement.js"></script>
</body>
</html>