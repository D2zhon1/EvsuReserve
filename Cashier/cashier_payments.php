<?php
session_start();

$user_name  = $_SESSION['user_name']  ?? 'Maria Santos';
$user_email = $_SESSION['user_email'] ?? 'maria.santos@evsu.edu.ph';
$first_name = explode(' ', $user_name)[0];

// ── Handle AJAX verify/reject action ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $payment_id = $_POST['payment_id'] ?? '';
    $action     = $_POST['action'];      // 'verified' | 'rejected'
    $order_id   = $_POST['order_id'] ?? '';

    // TODO: Replace with real DB update
    // e.g. UPDATE payments SET status=?, verified_by=?, verification_date=NOW() WHERE id=?
    // if ($action === 'verified') UPDATE orders SET payment_status='verified', status='paid' WHERE id=?

    if (in_array($action, ['verified', 'rejected']) && $payment_id) {
        echo json_encode(['success' => true, 'payment_id' => $payment_id, 'new_status' => $action]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// ── Filters from GET ──────────────────────────────────────────────────────
$status_filter = $_GET['status'] ?? 'pending';
$search        = trim($_GET['search'] ?? '');
$valid_statuses = ['all', 'pending', 'verified', 'rejected'];
if (!in_array($status_filter, $valid_statuses)) $status_filter = 'pending';

// ── Mock payments (replace with real DB query) ────────────────────────────
$all_payments = [
    [
        'id' => 'PAY-001', 'order_id' => 'ORD-001',
        'order_number' => 'ORD-2026-0145', 'payer_name' => 'Juan dela Cruz',
        'method' => 'GCash',        'amount' => 1250.00, 'status' => 'pending',
        'reference_number' => 'GC-9812734', 'proof_url' => '',
        'created_date' => '2026-05-16',
    ],
    [
        'id' => 'PAY-002', 'order_id' => 'ORD-002',
        'order_number' => 'ORD-2026-0146', 'payer_name' => 'Ana Reyes',
        'method' => 'Cash',         'amount' => 350.00,  'status' => 'pending',
        'reference_number' => '',   'proof_url' => '',
        'created_date' => '2026-05-16',
    ],
    [
        'id' => 'PAY-003', 'order_id' => 'ORD-003',
        'order_number' => 'ORD-2026-0143', 'payer_name' => 'Carlo Mendoza',
        'method' => 'PayMaya',      'amount' => 780.00,  'status' => 'verified',
        'reference_number' => 'PM-4421098', 'proof_url' => '',
        'created_date' => '2026-05-15',
    ],
    [
        'id' => 'PAY-004', 'order_id' => 'ORD-004',
        'order_number' => 'ORD-2026-0140', 'payer_name' => 'Liza Fernandez',
        'method' => 'GCash',        'amount' => 2100.00, 'status' => 'verified',
        'reference_number' => 'GC-7723001', 'proof_url' => '',
        'created_date' => '2026-05-14',
    ],
    [
        'id' => 'PAY-005', 'order_id' => 'ORD-005',
        'order_number' => 'ORD-2026-0139', 'payer_name' => 'Mark Bautista',
        'method' => 'Cash',         'amount' => 420.00,  'status' => 'rejected',
        'reference_number' => '',   'proof_url' => '',
        'created_date' => '2026-05-13',
    ],
    [
        'id' => 'PAY-006', 'order_id' => 'ORD-006',
        'order_number' => 'ORD-2026-0137', 'payer_name' => 'Grace Villanueva',
        'method' => 'Bank Transfer', 'amount' => 960.00, 'status' => 'pending',
        'reference_number' => 'BT-001-2026', 'proof_url' => '',
        'created_date' => '2026-05-16',
    ],
    [
        'id' => 'PAY-007', 'order_id' => 'ORD-007',
        'order_number' => 'ORD-2026-0135', 'payer_name' => 'Paolo Cruz',
        'method' => 'PayMaya',      'amount' => 550.00,  'status' => 'verified',
        'reference_number' => 'PM-5500213', 'proof_url' => '',
        'created_date' => '2026-05-12',
    ],
    [
        'id' => 'PAY-008', 'order_id' => 'ORD-008',
        'order_number' => 'ORD-2026-0134', 'payer_name' => 'Rica Morales',
        'method' => 'GCash',        'amount' => 1800.00, 'status' => 'rejected',
        'reference_number' => 'GC-1122334', 'proof_url' => '',
        'created_date' => '2026-05-11',
    ],
];

// ── Apply filters ─────────────────────────────────────────────────────────
$filtered = array_filter($all_payments, function ($p) use ($status_filter, $search) {
    $match_status = $status_filter === 'all' || $p['status'] === $status_filter;
    $match_search = $search === '' ||
        stripos($p['payer_name'],      $search) !== false ||
        stripos($p['order_number'],    $search) !== false ||
        stripos($p['reference_number'],$search) !== false;
    return $match_status && $match_search;
});

// ── Status badge config ───────────────────────────────────────────────────
$status_config = [
    'pending'  => ['label' => 'Pending',  'class' => 'badge-orange'],
    'verified' => ['label' => 'Verified', 'class' => 'badge-green'],
    'rejected' => ['label' => 'Rejected', 'class' => 'badge-red'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Payment Verification — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/cashier_payments.css"/>
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
    <a href="cashier_dashboard.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Dashboard
    </a>
    <a href="cashier_payments.php" class="nav-item active">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
        <line x1="1" y1="10" x2="23" y2="10"/>
      </svg>
      Payments
    </a>

    <a href="../Shared/cashier_reports.php" class="nav-item">
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
        <h1 class="page-title">Payment Verification</h1>
        <p class="page-sub">Verify and manage payment records</p>
      </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="" class="filters-bar" id="filterForm">
      <div class="search-wrap">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          type="text"
          name="search"
          class="search-input"
          placeholder="Search by payer, order, or reference…"
          value="<?= htmlspecialchars($search) ?>"
          autocomplete="off"
        />
      </div>
      <select name="status" class="filter-select" onchange="this.form.submit()">
        <option value="all"      <?= $status_filter === 'all'      ? 'selected' : '' ?>>All Status</option>
        <option value="pending"  <?= $status_filter === 'pending'  ? 'selected' : '' ?>>Pending</option>
        <option value="verified" <?= $status_filter === 'verified' ? 'selected' : '' ?>>Verified</option>
        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
      </select>
      <button type="submit" class="btn-search">Search</button>
    </form>

    <!-- Payments table card -->
    <div class="orders-card">

      <?php if (empty($filtered)): ?>
        <!-- Empty state -->
        <div class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="1.5"
               stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
            <line x1="1" y1="10" x2="23" y2="10"/>
          </svg>
          <p class="empty-title">No payments found</p>
          <p class="empty-sub">Try adjusting your search or status filter.</p>
        </div>

      <?php else: ?>
        <div class="orders-table-wrap">
          <table class="orders-table">
            <thead>
              <tr>
                <th>Order</th>
                <th>Payer</th>
                <th>Method</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($filtered as $payment):
                $sc       = $status_config[$payment['status']] ?? ['label' => ucfirst($payment['status']), 'class' => 'badge-gray'];
                $date_fmt = $payment['created_date'] ? date('M j, Y', strtotime($payment['created_date'])) : '—';
                // JSON-encode entire payment row for the modal
                $data_json = htmlspecialchars(json_encode($payment), ENT_QUOTES, 'UTF-8');
              ?>
              <tr data-payment='<?= $data_json ?>'>
                <td class="order-num"><?= htmlspecialchars($payment['order_number']) ?></td>
                <td><?= htmlspecialchars($payment['payer_name'] ?: '—') ?></td>
                <td class="text-muted"><?= htmlspecialchars($payment['method']) ?></td>
                <td class="order-total">₱<?= number_format($payment['amount'], 2) ?></td>
                <td><span class="badge <?= $sc['class'] ?>"><?= $sc['label'] ?></span></td>
                <td class="text-muted"><?= $date_fmt ?></td>
                <td>
                  <div class="action-btns">
                    <!-- View -->
                    <button class="action-btn btn-view" title="View details"
                            onclick="openModal(this.closest('tr').dataset.payment)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                           fill="none" stroke="currentColor" stroke-width="2"
                           stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                      </svg>
                    </button>

                    <?php if ($payment['status'] === 'pending'): ?>
                      <!-- Verify -->
                      <button class="action-btn btn-verify" title="Verify payment"
                              onclick="handleAction('<?= $payment['id'] ?>', 'verified', '<?= $payment['order_id'] ?>')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                          <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                      </button>
                      <!-- Reject -->
                      <button class="action-btn btn-reject" title="Reject payment"
                              onclick="handleAction('<?= $payment['id'] ?>', 'rejected', '<?= $payment['order_id'] ?>')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                          <circle cx="12" cy="12" r="10"/>
                          <line x1="15" y1="9" x2="9" y2="15"/>
                          <line x1="9"  y1="9" x2="15" y2="15"/>
                        </svg>
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
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

<!-- ══ PAYMENT DETAIL MODAL ══════════════════════════════════════════════ -->
<div class="modal-backdrop" id="modalBackdrop" onclick="closeModal(event)">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-header">
      <h2 class="modal-title" id="modalTitle">Payment Details</h2>
      <button class="modal-close" onclick="closeModal()" aria-label="Close">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6"  y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <div class="modal-body" id="modalBody"><!-- filled by JS --></div>
    <div class="modal-footer" id="modalFooter"><!-- filled by JS --></div>
  </div>
</div>

<!-- Toast notification -->
<div class="toast" id="toast"></div>

<script src="../JS/cashier_payments.js"></script>
</body>
</html>
