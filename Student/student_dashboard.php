<?php
session_start();

// Mock user — replace with actual session data
$user_name     = $_SESSION['user_name']  ?? 'Juan dela Cruz';
$first_name    = explode(' ', $user_name)[0];

// ── Mock data (replace with real DB queries) ──────────────────────────────
$total_orders     = 8;
$pending_orders   = 2;
$completed_orders = 5;
$cart_items       = 3;

$recent_orders = [
    ['id' => 'ORD-001',  'status' => 'completed', 'items' => 3, 'date' => '2026-05-10', 'total' => 1250.00, 'payment_status' => 'paid'],
    ['id' => 'ORD-002',  'status' => 'pending',   'items' => 1, 'date' => '2026-05-12', 'total' => 350.00,  'payment_status' => 'pending'],
    ['id' => 'ORD-003',  'status' => 'processing','items' => 2, 'date' => '2026-05-13', 'total' => 780.00,  'payment_status' => 'pending'],
    ['id' => 'ORD-004',  'status' => 'completed', 'items' => 4, 'date' => '2026-04-28', 'total' => 2100.00, 'payment_status' => 'verified'],
    ['id' => 'ORD-005',  'status' => 'cancelled', 'items' => 1, 'date' => '2026-04-15', 'total' => 420.00,  'payment_status' => 'refunded'],
];

// Status badge config
$status_config = [
    'completed'  => ['label' => 'Completed',  'class' => 'badge-green'],
    'pending'    => ['label' => 'Pending',     'class' => 'badge-orange'],
    'processing' => ['label' => 'Processing',  'class' => 'badge-blue'],
    'cancelled'  => ['label' => 'Cancelled',   'class' => 'badge-red'],
];
// ─────────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/student_dashboard.css"/>
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
  </div>

  <nav class="sidebar-nav">
    <a href="dashboard.php" class="nav-item active">
      <!-- Home -->
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Dashboard
    </a>
    <a href="student_product.php" class="nav-item">
      <!-- ShoppingBag -->
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
        <line x1="3" y1="6" x2="21" y2="6"/>
        <path d="M16 10a4 4 0 0 1-8 0"/>
      </svg>
      Products
    </a>
    <a href="orders.php" class="nav-item">
      <!-- ClipboardList -->
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
        <path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/>
      </svg>
      My Orders
    </a>
    <a href="cart.php" class="nav-item">
      <!-- ShoppingCart -->
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
      </svg>
      Cart
      <?php if ($cart_items > 0): ?>
        <span class="nav-badge"><?= $cart_items ?></span>
      <?php endif; ?>
    </a>
    <a href="profile.php" class="nav-item">
      <!-- User -->
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      Profile
    </a>
  </nav>

  <div class="sidebar-bottom">
    <a href="logout.php" class="nav-item nav-logout">
      <!-- LogOut -->
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
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
    <div class="topbar-right">
      <a href="cart.php" class="topbar-cart">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
        <?php if ($cart_items > 0): ?>
          <span class="cart-dot"><?= $cart_items ?></span>
        <?php endif; ?>
      </a>
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
        <h1 class="page-title">Welcome back, <?= htmlspecialchars($first_name) ?>!</h1>
        <p class="page-sub">Your EVSU Reserve dashboard</p>
      </div>
      <a href="student.product.php" class="btn-shop">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <path d="M16 10a4 4 0 0 1-8 0"/>
        </svg>
        Shop Now
      </a>
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
          <span class="stat-label">Pending</span>
          <span class="stat-value"><?= $pending_orders ?></span>
        </div>
      </div>

      <div class="stat-card stat-green">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Completed</span>
          <span class="stat-value"><?= $completed_orders ?></span>
        </div>
      </div>

      <div class="stat-card stat-blue">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Cart Items</span>
          <span class="stat-value"><?= $cart_items ?></span>
        </div>
      </div>

    </div><!-- /stats-grid -->

    <!-- Recent Orders card -->
    <div class="orders-card">
      <div class="orders-card-header">
        <h2 class="orders-title">Recent Orders</h2>
        <a href="orders.php" class="view-all-link">View all</a>
      </div>

      <?php if (empty($recent_orders)): ?>
        <!-- Empty state -->
        <div class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="1.5"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
          <p class="empty-title">No orders yet</p>
          <p class="empty-sub">Start shopping to see your orders here.</p>
          <a href="student_product.php" class="btn-shop btn-shop-sm">Browse Products</a>
        </div>

      <?php else: ?>
        <div class="orders-table-wrap">
          <table class="orders-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Status</th>
                <th>Items</th>
                <th>Date</th>
                <th class="text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_slice($recent_orders, 0, 5) as $order):
                $sc = $status_config[$order['status']] ?? ['label' => ucfirst($order['status']), 'class' => 'badge-gray'];
                $date_fmt = date('M j, Y', strtotime($order['date']));
              ?>
              <tr>
                <td class="order-num"><?= htmlspecialchars($order['id']) ?></td>
                <td><span class="badge <?= $sc['class'] ?>"><?= $sc['label'] ?></span></td>
                <td class="text-muted"><?= $order['items'] ?> item<?= $order['items'] !== 1 ? 's' : '' ?></td>
                <td class="text-muted"><?= $date_fmt ?></td>
                <td class="order-total text-right">₱<?= number_format($order['total'], 2) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div><!-- /orders-card -->

  </main>
</div><!-- /main-wrap -->

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<script src="../JS/student_dashboard.js"></script>
</body>
</html>