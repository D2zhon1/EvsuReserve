<?php
session_start();

require_once __DIR__ . '/../database.php';

$user_name  = $_SESSION['user_name'] ?? 'Admin User';
$first_name = explode(' ', $user_name)[0];

$period = isset($_GET['period']) ? (int) $_GET['period'] : 30;
$valid_periods = [7, 30, 90, 365];
if (!in_array($period, $valid_periods, true)) {
    $period = 30;
}

$cutoff_date = date('Y-m-d', strtotime("-{$period} days"));

$all_orders = [];
$stmt = $conn->prepare(
    "SELECT order_number AS id, payment_status, total_amount, DATE(created_at) AS created_date, id AS order_pk
     FROM orders WHERE DATE(created_at) >= ? ORDER BY created_at DESC"
);
$stmt->bind_param('s', $cutoff_date);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $order_pk = (int) $row['order_pk'];
    unset($row['order_pk']);
    $row['total_amount'] = (float) $row['total_amount'];

    $items = [];
    $istmt = $conn->prepare(
        'SELECT product_name, quantity, subtotal FROM order_items WHERE order_id = ?'
    );
    $istmt->bind_param('i', $order_pk);
    $istmt->execute();
    $ires = $istmt->get_result();
    while ($item = $ires->fetch_assoc()) {
        $item['subtotal'] = (float) $item['subtotal'];
        $item['quantity'] = (int) $item['quantity'];
        $items[] = $item;
    }
    $istmt->close();

    $row['items'] = $items;
    $all_orders[] = $row;
}
$stmt->close();

$all_payments = [];
$stmt = $conn->prepare(
    "SELECT method, status, amount, DATE(created_at) AS created_date
     FROM payments WHERE DATE(created_at) >= ? ORDER BY created_at DESC"
);
$stmt->bind_param('s', $cutoff_date);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $row['amount'] = (float) $row['amount'];
    $all_payments[] = $row;
}
$stmt->close();

$filtered_orders   = array_filter($all_orders, fn($o) => $o['created_date'] >= $cutoff_date);
$filtered_payments = array_filter($all_payments, fn($p) => $p['created_date'] >= $cutoff_date);

$paid_orders = array_filter($filtered_orders, fn($o) =>
    in_array($o['payment_status'], ['verified', 'paid'])
);
$total_sales       = array_sum(array_column(array_values($paid_orders), 'total_amount'));
$total_orders      = count($filtered_orders);
$avg_order_value   = $total_orders > 0 ? $total_sales / $total_orders : 0;
$verified_payments = count(array_filter($filtered_payments, fn($p) => $p['status'] === 'verified'));

$daily_labels  = [];
$daily_revenue = [];
$daily_orders  = [];

for ($i = $period; $i >= 0; $i--) {
    $day_str   = date('Y-m-d', strtotime("-{$i} days"));
    $day_label = date('M j', strtotime($day_str));

    $day_orders = array_filter($filtered_orders, fn($o) => $o['created_date'] === $day_str);
    $day_rev    = array_sum(array_column(array_values($day_orders), 'total_amount'));

    $daily_labels[]  = $day_label;
    $daily_revenue[] = $day_rev;
    $daily_orders[]  = count($day_orders);
}

$label_step = max(1, (int) ceil(($period + 1) / 12));
$sparse_labels = [];
foreach ($daily_labels as $i => $label) {
    $sparse_labels[] = ($i % $label_step === 0) ? $label : '';
}

$product_sales = [];
foreach ($filtered_orders as $order) {
    foreach (($order['items'] ?? []) as $item) {
        $name = $item['product_name'];
        if (!isset($product_sales[$name])) {
            $product_sales[$name] = ['name' => $name, 'quantity' => 0, 'revenue' => 0];
        }
        $product_sales[$name]['quantity'] += $item['quantity'] ?? 0;
        $product_sales[$name]['revenue']  += $item['subtotal'] ?? 0;
    }
}
usort($product_sales, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
$top_products = array_slice($product_sales, 0, 5);

$method_counts = [];
foreach ($filtered_payments as $p) {
    $m = $p['method'];
    $method_counts[$m] = ($method_counts[$m] ?? 0) + 1;
}
arsort($method_counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reports — EVSU Reserve Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/cashier_reports.css"/>
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
    <a href="admin_dashboard.php" class="nav-item">
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
    <a href="admin_reports.php" class="nav-item active">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="20" x2="18" y2="10"/>
        <line x1="12" y1="20" x2="12" y2="4"/>
        <line x1="6"  y1="20" x2="6"  y2="14"/>
      </svg>
      Reports
    </a>
    <a href="admin_logs.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/>
        <polyline points="10 9 9 9 8 9"/>
      </svg>
      Activity Logs
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

    <div class="page-header">
      <div>
        <h1 class="page-title">Reports &amp; Analytics</h1>
        <p class="page-sub">Sales performance and insights</p>
      </div>
      <form method="GET" action="" class="period-form">
        <select name="period" class="period-select" onchange="this.form.submit()">
          <option value="7"   <?= $period === 7   ? 'selected' : '' ?>>Last 7 days</option>
          <option value="30"  <?= $period === 30  ? 'selected' : '' ?>>Last 30 days</option>
          <option value="90"  <?= $period === 90  ? 'selected' : '' ?>>Last 90 days</option>
          <option value="365" <?= $period === 365 ? 'selected' : '' ?>>Last year</option>
        </select>
      </form>
    </div>

    <div class="stats-grid">

      <div class="stat-card stat-green">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="1" x2="12" y2="23"/>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Total Sales</span>
          <span class="stat-value">₱<?= number_format($total_sales, 0) ?></span>
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
          <span class="stat-label">Orders</span>
          <span class="stat-value"><?= $total_orders ?></span>
        </div>
      </div>

      <div class="stat-card stat-maroon">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Avg Order Value</span>
          <span class="stat-value">₱<?= number_format($avg_order_value, 0) ?></span>
        </div>
      </div>

      <div class="stat-card stat-orange">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Verified Payments</span>
          <span class="stat-value"><?= $verified_payments ?></span>
        </div>
      </div>

    </div>

    <div class="chart-card anim-delay-1">
      <div class="chart-card-header">
        <h3 class="chart-title">Revenue Trend</h3>
        <span class="chart-sub">Last <?= $period ?> days</span>
      </div>
      <div class="chart-wrap">
        <canvas id="revenueChart"></canvas>
      </div>
    </div>

    <div class="charts-row">

      <div class="chart-card anim-delay-2">
        <div class="chart-card-header">
          <h3 class="chart-title">Top Products</h3>
          <span class="chart-sub">by revenue</span>
        </div>
        <?php if (empty($top_products)): ?>
          <div class="chart-empty">No product data for this period</div>
        <?php else: ?>
          <div class="chart-wrap chart-wrap-sm">
            <canvas id="productsChart"></canvas>
          </div>
        <?php endif; ?>
      </div>

      <div class="chart-card anim-delay-3">
        <div class="chart-card-header">
          <h3 class="chart-title">Payment Methods</h3>
          <span class="chart-sub">distribution</span>
        </div>
        <?php if (empty($method_counts)): ?>
          <div class="chart-empty">No payment data for this period</div>
        <?php else: ?>
          <div class="chart-wrap chart-wrap-sm chart-wrap-pie">
            <canvas id="methodsChart"></canvas>
          </div>
          <div class="pie-legend" id="pieLegend"></div>
        <?php endif; ?>
      </div>

    </div>

  </main>
</div>

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
  const CHART_DATA = {
    revenue: {
      labels:  <?= json_encode($sparse_labels) ?>,
      revenue: <?= json_encode($daily_revenue) ?>,
      orders:  <?= json_encode($daily_orders) ?>,
      allLabels: <?= json_encode($daily_labels) ?>,
    },
    products: {
      names:   <?= json_encode(array_column($top_products, 'name')) ?>,
      revenue: <?= json_encode(array_column($top_products, 'revenue')) ?>,
    },
    methods: {
      labels: <?= json_encode(array_keys($method_counts)) ?>,
      values: <?= json_encode(array_values($method_counts)) ?>,
    }
  };
</script>
<script src="../JS/cashier_reports.js"></script>
</body>
</html>
