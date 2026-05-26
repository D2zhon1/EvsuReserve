<?php
session_start();

// Mock user — replace with actual session data
$user_name  = $_SESSION['user_name'] ?? 'Maria Santos';
$first_name = explode(' ', $user_name)[0];

// ── Mock data (replace with real DB queries) ──────────────────────────────
$payments = [
    ['id' => 'PAY-001', 'order_number' => 'ORD-2026-0145', 'payer_name' => 'Juan dela Cruz',    'method' => 'GCash',       'amount' => 1250.00, 'status' => 'pending',  'created_date' => '2026-05-16 08:14:00'],
    ['id' => 'PAY-002', 'order_number' => 'ORD-2026-0146', 'payer_name' => 'Ana Reyes',          'method' => 'Cash',        'amount' => 350.00,  'status' => 'pending',  'created_date' => '2026-05-16 09:02:00'],
    ['id' => 'PAY-003', 'order_number' => 'ORD-2026-0143', 'payer_name' => 'Carlo Mendoza',      'method' => 'PayMaya',     'amount' => 780.00,  'status' => 'verified', 'created_date' => '2026-05-16 07:45:00'],
    ['id' => 'PAY-004', 'order_number' => 'ORD-2026-0140', 'payer_name' => 'Liza Fernandez',     'method' => 'GCash',       'amount' => 2100.00, 'status' => 'verified', 'created_date' => '2026-05-16 06:30:00'],
    ['id' => 'PAY-005', 'order_number' => 'ORD-2026-0139', 'payer_name' => 'Mark Bautista',      'method' => 'Cash',        'amount' => 420.00,  'status' => 'rejected', 'created_date' => '2026-05-15 14:20:00'],
    ['id' => 'PAY-006', 'order_number' => 'ORD-2026-0137', 'payer_name' => 'Grace Villanueva',   'method' => 'Bank Transfer','amount' => 960.00, 'status' => 'pending',  'created_date' => '2026-05-16 10:11:00'],
    ['id' => 'PAY-007', 'order_number' => 'ORD-2026-0135', 'payer_name' => 'Paolo Cruz',         'method' => 'GCash',       'amount' => 550.00,  'status' => 'verified', 'created_date' => '2026-05-16 05:58:00'],
    ['id' => 'PAY-008', 'order_number' => 'ORD-2026-0134', 'payer_name' => 'Rica Morales',       'method' => 'PayMaya',     'amount' => 1800.00, 'status' => 'rejected', 'created_date' => '2026-05-15 11:30:00'],
];

$today = date('Y-m-d');

$pending_payments  = array_filter($payments, fn($p) => $p['status'] === 'pending');
$verified_payments = array_filter($payments, fn($p) => $p['status'] === 'verified');
$rejected_payments = array_filter($payments, fn($p) => $p['status'] === 'rejected');

$today_verified = array_filter($verified_payments, fn($p) =>
    date('Y-m-d', strtotime($p['created_date'])) === $today
);

$total_verified_amount = array_sum(array_column(array_values($verified_payments), 'amount'));

// Status badge config
$status_config = [
    'pending'  => ['label' => 'Pending',  'class' => 'badge-orange'],
    'verified' => ['label' => 'Verified', 'class' => 'badge-green'],
    'rejected' => ['label' => 'Rejected', 'class' => 'badge-red'],
];

// Method icon map (emoji fallback — swap for SVG if preferred)
$method_icons = [
    'GCash'        => '💙',
    'PayMaya'      => '💚',
    'Cash'         => '💵',
    'Bank Transfer'=> '🏦',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cashier Dashboard — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/cashier_dashboard.css"/>
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
    <a href="../Cashier/cashier_dashboard.php" class="nav-item active">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Dashboard
    </a>
    <a href="../Cashier/cashier_payments.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
        <line x1="1" y1="10" x2="23" y2="10"/>
      </svg>
      Payments
      <?php if (count($pending_payments) > 0): ?>
        <span class="nav-badge"><?= count($pending_payments) ?></span>
      <?php endif; ?>
    </a>

    <a href="../Shared/cashier_reports.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
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
        <h1 class="page-title">Cashier Dashboard</h1>
        <p class="page-sub">Welcome, <?= htmlspecialchars($user_name) ?></p>
      </div>
      <a href="cashier_payments.php" class="btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
          <line x1="1" y1="10" x2="23" y2="10"/>
        </svg>
        Verify Payments
      </a>
    </div>

    <!-- Stats grid -->
    <div class="stats-grid">

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
          <span class="stat-label">Pending Verification</span>
          <span class="stat-value"><?= count($pending_payments) ?></span>
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
          <span class="stat-label">Verified Today</span>
          <span class="stat-value"><?= count($today_verified) ?></span>
        </div>
      </div>

      <div class="stat-card stat-blue">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="1" x2="12" y2="23"/>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Total Verified</span>
          <span class="stat-value">₱<?= number_format($total_verified_amount, 0) ?></span>
        </div>
      </div>

      <div class="stat-card stat-red">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
            <line x1="9"  y1="9" x2="15" y2="15"/>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Rejected</span>
          <span class="stat-value"><?= count($rejected_payments) ?></span>
        </div>
      </div>

    </div><!-- /stats-grid -->

    <!-- Pending Verifications card -->
    <div class="orders-card">
      <div class="orders-card-header">
        <h2 class="orders-title">Pending Verifications</h2>
        <a href="cashier_payments.php" class="view-all-link">View all</a>
      </div>

      <?php if (empty($pending_payments)): ?>
        <div class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="1.5"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
          <p class="empty-title">All caught up!</p>
          <p class="empty-sub">No pending payments to verify right now.</p>
        </div>

      <?php else: ?>
        <div class="payments-list">
          <?php foreach (array_slice(array_values($pending_payments), 0, 8) as $payment):
            $sc     = $status_config[$payment['status']] ?? ['label' => ucfirst($payment['status']), 'class' => 'badge-gray'];
            $icon   = $method_icons[$payment['method']] ?? '💳';
          ?>
          <div class="payment-row">
            <div class="payment-left">
              <div class="payment-method-icon"><?= $icon ?></div>
              <div class="payment-info">
                <p class="payment-order"><?= htmlspecialchars($payment['order_number']) ?></p>
                <p class="payment-meta">
                  <?= htmlspecialchars($payment['payer_name']) ?>
                  <span class="meta-sep">·</span>
                  <?= htmlspecialchars($payment['method']) ?>
                </p>
              </div>
            </div>
            <div class="payment-right">
              <span class="badge <?= $sc['class'] ?>"><?= $sc['label'] ?></span>
              <span class="payment-amount">₱<?= number_format($payment['amount'], 2) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div><!-- /orders-card -->

  </main>
</div><!-- /main-wrap -->

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<script src="../JS/cashier_dashboard.js"></script>
</body>
</html>
