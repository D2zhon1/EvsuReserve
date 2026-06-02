<?php
session_start();

require_once __DIR__ . '/../database.php';

$user_name  = $_SESSION['user_name']  ?? 'Admin User';
$first_name = explode(' ', $user_name)[0];

$all_users = [];
$res = $conn->query('SELECT id, full_name AS name, role FROM users ORDER BY id');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $row['id'] = 'U' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
        $all_users[] = $row;
    }
}

$all_products = [];
$res = $conn->query('SELECT id, name, category FROM products WHERE is_active = 1 ORDER BY id');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $row['id'] = 'P' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
        $all_products[] = $row;
    }
}

$all_orders = [];
$res = $conn->query('SELECT order_number AS id, status, payment_status, total_amount FROM orders ORDER BY created_at DESC');
if ($res) {
    $all_orders = $res->fetch_all(MYSQLI_ASSOC);
    foreach ($all_orders as &$o) {
        $o['total_amount'] = (float) $o['total_amount'];
    }
    unset($o);
}

// ── Computed stats ─────────────────────────────────────────────────────────
$total_users    = count($all_users);
$total_products = count($all_products);
$total_orders   = count($all_orders);

$total_revenue = array_sum(array_column(
    array_filter($all_orders, fn($o) => in_array($o['payment_status'], ['verified', 'paid'])),
    'total_amount'
));

// ── Orders by status ───────────────────────────────────────────────────────
$order_statuses = ['pending', 'paid', 'processing', 'ready', 'completed', 'cancelled'];
$order_status_data = [];
foreach ($order_statuses as $s) {
    $count = count(array_filter($all_orders, fn($o) => $o['status'] === $s));
    if ($count > 0) {
        $order_status_data[] = ['name' => ucfirst($s), 'count' => $count];
    }
}

// ── Products by category ───────────────────────────────────────────────────
$category_counts = [];
foreach ($all_products as $p) {
    $cat = $p['category'] ?? 'other';
    $category_counts[$cat] = ($category_counts[$cat] ?? 0) + 1;
}
$category_data = [];
foreach ($category_counts as $cat => $count) {
    $category_data[] = ['name' => ucwords(str_replace('_', ' ', $cat)), 'value' => $count];
}

// ── Users by role ──────────────────────────────────────────────────────────
$roles = ['student', 'cashier', 'staff', 'admin'];
$role_counts = [];
foreach ($roles as $role) {
    $role_counts[$role] = count(array_filter($all_users, fn($u) => $u['role'] === $role));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/admin_dashboard.css"/>
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
    <div class="role-pill">Admin</div>
  </div>

  <nav class="sidebar-nav">
    <a href="admin_dashboard.php" class="nav-item active">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Dashboard
    </a>
    <a href="admin_users.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      Users
    </a>

    <a href="admin_settings.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="3"/>
        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>
      </svg>
      Settings
    </a>
  </nav>

  <div class="sidebar-bottom">
    <a href="../logout.php" class="nav-item nav-logout">
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

  <main class="page-content">

    <!-- Page header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Admin Dashboard</h1>
        <p class="page-sub">System overview &bull; <?= htmlspecialchars($user_name) ?></p>
      </div>
    </div>

    <!-- Stats grid -->
    <div class="stats-grid">

      <div class="stat-card stat-maroon">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Total Users</span>
          <span class="stat-value"><?= $total_users ?></span>
        </div>
      </div>

      <div class="stat-card stat-blue">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Products</span>
          <span class="stat-value"><?= $total_products ?></span>
        </div>
      </div>

      <div class="stat-card stat-orange">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

      <div class="stat-card stat-green">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="1" x2="12" y2="23"/>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Revenue</span>
          <span class="stat-value">₱<?= number_format($total_revenue, 0) ?></span>
        </div>
      </div>

    </div><!-- /stats-grid -->

    <!-- Charts row -->
    <div class="charts-row">

      <!-- Orders by Status — Bar Chart -->
      <div class="chart-card">
        <div class="chart-card-header">
          <h3 class="chart-title">Orders by Status</h3>
        </div>
        <div class="chart-wrap">
          <canvas id="ordersChart"></canvas>
        </div>
      </div>

      <!-- Products by Category — Doughnut Chart -->
      <div class="chart-card">
        <div class="chart-card-header">
          <h3 class="chart-title">Products by Category</h3>
        </div>
        <?php if (empty($category_data)): ?>
          <div class="chart-empty">No product data available</div>
        <?php else: ?>
          <div class="chart-wrap chart-wrap-pie">
            <canvas id="categoryChart"></canvas>
          </div>
          <div class="pie-legend" id="categoryLegend"></div>
        <?php endif; ?>
      </div>

    </div><!-- /charts-row -->

    <!-- Users by Role -->
    <div class="chart-card roles-card">
      <div class="chart-card-header">
        <h3 class="chart-title">Users by Role</h3>
      </div>
      <div class="roles-grid">

        <?php
        $role_icons = [
            'student' => '<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
            'cashier' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
            'staff'   => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'admin'   => '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>',
        ];
        $role_colors = [
            'student' => 'role-maroon',
            'cashier' => 'role-blue',
            'staff'   => 'role-green',
            'admin'   => 'role-gold',
        ];
        foreach ($roles as $role):
        ?>
        <div class="role-card <?= $role_colors[$role] ?>">
          <div class="role-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <?= $role_icons[$role] ?>
            </svg>
          </div>
          <span class="role-count"><?= $role_counts[$role] ?></span>
          <span class="role-label"><?= ucfirst($role) ?>s</span>
        </div>
        <?php endforeach; ?>

      </div>
    </div>

  </main>
</div><!-- /main-wrap -->

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Chart.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<!-- Pass PHP data to JS -->
<script>
  const CHART_DATA = {
    orderStatus: {
      labels: <?= json_encode(array_column($order_status_data, 'name')) ?>,
      counts: <?= json_encode(array_column($order_status_data, 'count')) ?>,
    },
    category: {
      labels: <?= json_encode(array_column($category_data, 'name')) ?>,
      values: <?= json_encode(array_column($category_data, 'value')) ?>,
    },
  };
</script>
<script src="../JS/admin_dashboard.js"></script>
</body>
</html>
