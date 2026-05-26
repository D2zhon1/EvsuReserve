<?php
session_start();

// Mock user
$user_name  = $_SESSION['user_name'] ?? 'Maria Santos';
$first_name = explode(' ', $user_name)[0];

// ── Period filter ──────────────────────────────────────────────────────────
$period = isset($_GET['period']) ? (int)$_GET['period'] : 30;
$valid_periods = [7, 30, 90, 365];
if (!in_array($period, $valid_periods)) $period = 30;

$cutoff_date = date('Y-m-d', strtotime("-{$period} days"));
$today       = date('Y-m-d');

// ── Mock Orders (replace with real DB queries) ─────────────────────────────
$all_orders = [
    ['id' => 'ORD-001', 'payment_status' => 'verified', 'total_amount' => 1250.00, 'created_date' => date('Y-m-d', strtotime('-1 days')),  'items' => [['product_name' => 'PE Uniform', 'quantity' => 2, 'subtotal' => 800], ['product_name' => 'School ID', 'quantity' => 1, 'subtotal' => 450]]],
    ['id' => 'ORD-002', 'payment_status' => 'pending',  'total_amount' => 350.00,  'created_date' => date('Y-m-d', strtotime('-2 days')),  'items' => [['product_name' => 'Laboratory Gown', 'quantity' => 1, 'subtotal' => 350]]],
    ['id' => 'ORD-003', 'payment_status' => 'paid',     'total_amount' => 780.00,  'created_date' => date('Y-m-d', strtotime('-2 days')),  'items' => [['product_name' => 'PE Uniform', 'quantity' => 1, 'subtotal' => 400], ['product_name' => 'Polo Shirt', 'quantity' => 1, 'subtotal' => 380]]],
    ['id' => 'ORD-004', 'payment_status' => 'verified', 'total_amount' => 2100.00, 'created_date' => date('Y-m-d', strtotime('-4 days')),  'items' => [['product_name' => 'Polo Shirt', 'quantity' => 3, 'subtotal' => 1140], ['product_name' => 'PE Uniform', 'quantity' => 2, 'subtotal' => 800], ['product_name' => 'School ID', 'quantity' => 1, 'subtotal' => 160]]],
    ['id' => 'ORD-005', 'payment_status' => 'verified', 'total_amount' => 420.00,  'created_date' => date('Y-m-d', strtotime('-5 days')),  'items' => [['product_name' => 'Laboratory Gown', 'quantity' => 1, 'subtotal' => 350], ['product_name' => 'School ID', 'quantity' => 1, 'subtotal' => 70]]],
    ['id' => 'ORD-006', 'payment_status' => 'paid',     'total_amount' => 960.00,  'created_date' => date('Y-m-d', strtotime('-6 days')),  'items' => [['product_name' => 'Polo Shirt', 'quantity' => 2, 'subtotal' => 760], ['product_name' => 'School ID', 'quantity' => 1, 'subtotal' => 200]]],
    ['id' => 'ORD-007', 'payment_status' => 'verified', 'total_amount' => 550.00,  'created_date' => date('Y-m-d', strtotime('-8 days')),  'items' => [['product_name' => 'PE Uniform', 'quantity' => 1, 'subtotal' => 400], ['product_name' => 'School ID', 'quantity' => 1, 'subtotal' => 150]]],
    ['id' => 'ORD-008', 'payment_status' => 'pending',  'total_amount' => 1800.00, 'created_date' => date('Y-m-d', strtotime('-10 days')), 'items' => [['product_name' => 'Polo Shirt', 'quantity' => 4, 'subtotal' => 1520], ['product_name' => 'School ID', 'quantity' => 1, 'subtotal' => 280]]],
    ['id' => 'ORD-009', 'payment_status' => 'verified', 'total_amount' => 670.00,  'created_date' => date('Y-m-d', strtotime('-12 days')), 'items' => [['product_name' => 'Laboratory Gown', 'quantity' => 1, 'subtotal' => 350], ['product_name' => 'Polo Shirt', 'quantity' => 1, 'subtotal' => 320]]],
    ['id' => 'ORD-010', 'payment_status' => 'paid',     'total_amount' => 1100.00, 'created_date' => date('Y-m-d', strtotime('-15 days')), 'items' => [['product_name' => 'PE Uniform', 'quantity' => 2, 'subtotal' => 800], ['product_name' => 'Laboratory Gown', 'quantity' => 1, 'subtotal' => 300]]],
    ['id' => 'ORD-011', 'payment_status' => 'verified', 'total_amount' => 890.00,  'created_date' => date('Y-m-d', strtotime('-18 days')), 'items' => [['product_name' => 'Polo Shirt', 'quantity' => 2, 'subtotal' => 760], ['product_name' => 'School ID', 'quantity' => 1, 'subtotal' => 130]]],
    ['id' => 'ORD-012', 'payment_status' => 'verified', 'total_amount' => 440.00,  'created_date' => date('Y-m-d', strtotime('-22 days')), 'items' => [['product_name' => 'Laboratory Gown', 'quantity' => 1, 'subtotal' => 350], ['product_name' => 'School ID', 'quantity' => 1, 'subtotal' => 90]]],
];

// ── Mock Payments ──────────────────────────────────────────────────────────
$all_payments = [
    ['method' => 'GCash',        'status' => 'verified', 'amount' => 1250.00, 'created_date' => date('Y-m-d', strtotime('-1 days'))],
    ['method' => 'Cash',         'status' => 'pending',  'amount' => 350.00,  'created_date' => date('Y-m-d', strtotime('-2 days'))],
    ['method' => 'PayMaya',      'status' => 'verified', 'amount' => 780.00,  'created_date' => date('Y-m-d', strtotime('-2 days'))],
    ['method' => 'GCash',        'status' => 'verified', 'amount' => 2100.00, 'created_date' => date('Y-m-d', strtotime('-4 days'))],
    ['method' => 'Cash',         'status' => 'verified', 'amount' => 420.00,  'created_date' => date('Y-m-d', strtotime('-5 days'))],
    ['method' => 'Bank Transfer','status' => 'verified', 'amount' => 960.00,  'created_date' => date('Y-m-d', strtotime('-6 days'))],
    ['method' => 'PayMaya',      'status' => 'verified', 'amount' => 550.00,  'created_date' => date('Y-m-d', strtotime('-8 days'))],
    ['method' => 'GCash',        'status' => 'rejected', 'amount' => 1800.00, 'created_date' => date('Y-m-d', strtotime('-10 days'))],
    ['method' => 'Cash',         'status' => 'verified', 'amount' => 670.00,  'created_date' => date('Y-m-d', strtotime('-12 days'))],
    ['method' => 'GCash',        'status' => 'verified', 'amount' => 1100.00, 'created_date' => date('Y-m-d', strtotime('-15 days'))],
    ['method' => 'Bank Transfer','status' => 'verified', 'amount' => 890.00,  'created_date' => date('Y-m-d', strtotime('-18 days'))],
    ['method' => 'PayMaya',      'status' => 'verified', 'amount' => 440.00,  'created_date' => date('Y-m-d', strtotime('-22 days'))],
];

// ── Filter by period ───────────────────────────────────────────────────────
$filtered_orders = array_filter($all_orders, fn($o) => $o['created_date'] >= $cutoff_date);
$filtered_payments = array_filter($all_payments, fn($p) => $p['created_date'] >= $cutoff_date);

// ── Stats ──────────────────────────────────────────────────────────────────
$paid_orders = array_filter($filtered_orders, fn($o) =>
    in_array($o['payment_status'], ['verified', 'paid'])
);
$total_sales       = array_sum(array_column(array_values($paid_orders), 'total_amount'));
$total_orders      = count($filtered_orders);
$avg_order_value   = $total_orders > 0 ? $total_sales / $total_orders : 0;
$verified_payments = count(array_filter($filtered_payments, fn($p) => $p['status'] === 'verified'));

// ── Daily sales chart data ─────────────────────────────────────────────────
$daily_labels   = [];
$daily_revenue  = [];
$daily_orders   = [];

for ($i = $period; $i >= 0; $i--) {
    $day_str    = date('Y-m-d', strtotime("-{$i} days"));
    $day_label  = date('M j', strtotime($day_str));

    $day_orders = array_filter($filtered_orders, fn($o) => $o['created_date'] === $day_str);
    $day_rev    = array_sum(array_column(array_values($day_orders), 'total_amount'));

    $daily_labels[]  = $day_label;
    $daily_revenue[] = $day_rev;
    $daily_orders[]  = count($day_orders);
}

// Only show every Nth label to avoid crowding (max ~12 visible)
$label_step = max(1, (int)ceil(($period + 1) / 12));
$sparse_labels = [];
foreach ($daily_labels as $i => $label) {
    $sparse_labels[] = ($i % $label_step === 0) ? $label : '';
}

// ── Top Products ───────────────────────────────────────────────────────────
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

// ── Payment Methods ────────────────────────────────────────────────────────
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
  <title>Reports — EVSU Reserve</title>
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
    <div class="role-pill">Cashier</div>
  </div>

  <nav class="sidebar-nav">
    <a href="/Cashier/cashier_dashboard.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Dashboard
    </a>
    <a href="../Cashier/cashier_payments.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
        <line x1="1" y1="10" x2="23" y2="10"/>
      </svg>
      Payments
    </a>

    <a href="../Shared/cashier_reports.php" class="nav-item active">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="20" x2="18" y2="10"/>
        <line x1="12" y1="20" x2="12" y2="4"/>
        <line x1="6"  y1="20" x2="6"  y2="14"/>
      </svg>
      Reports
    </a>
  </nav>

  <div class="sidebar-bottom">
    <a href="logout.php" class="nav-item nav-logout">
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
        <h1 class="page-title">Reports &amp; Analytics</h1>
        <p class="page-sub">Sales performance and insights</p>
      </div>
      <!-- Period selector (form GET) -->
      <form method="GET" action="" class="period-form">
        <select name="period" class="period-select" onchange="this.form.submit()">
          <option value="7"   <?= $period === 7   ? 'selected' : '' ?>>Last 7 days</option>
          <option value="30"  <?= $period === 30  ? 'selected' : '' ?>>Last 30 days</option>
          <option value="90"  <?= $period === 90  ? 'selected' : '' ?>>Last 90 days</option>
          <option value="365" <?= $period === 365 ? 'selected' : '' ?>>Last year</option>
        </select>
      </form>
    </div>

    <!-- Stats grid -->
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

    </div><!-- /stats-grid -->

    <!-- Revenue Trend Line Chart -->
    <div class="chart-card anim-delay-1">
      <div class="chart-card-header">
        <h3 class="chart-title">Revenue Trend</h3>
        <span class="chart-sub">Last <?= $period ?> days</span>
      </div>
      <div class="chart-wrap">
        <canvas id="revenueChart"></canvas>
      </div>
    </div>

    <!-- Bottom row: Top Products + Payment Methods -->
    <div class="charts-row">

      <!-- Top Products Horizontal Bar Chart -->
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

      <!-- Payment Methods Doughnut Chart -->
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
          <!-- Legend -->
          <div class="pie-legend" id="pieLegend"></div>
        <?php endif; ?>
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
