<?php
session_start();

// ── Mock data — replace with real DB queries ──────────────────────────────
$orders = [
    [
        'id'             => 'a1b2c3',
        'order_number'   => 'ORD-001',
        'customer_name'  => 'Juan dela Cruz',
        'customer_email' => 'juan@evsu.edu.ph',
        'total_amount'   => 1250.00,
        'payment_method' => 'GCash',
        'payment_status' => 'paid',
        'status'         => 'processing',
        'created_date'   => '2026-05-15',
        'notes'          => 'Please double-check size.',
        'items'          => [
            ['product_name' => 'EVSU Polo Shirt', 'size' => 'L',  'quantity' => 2, 'subtotal' => 900.00],
            ['product_name' => 'College Pin',     'size' => '',   'quantity' => 1, 'subtotal' => 350.00],
        ],
    ],
    [
        'id'             => 'd4e5f6',
        'order_number'   => 'ORD-002',
        'customer_name'  => 'Ana Reyes',
        'customer_email' => 'ana@evsu.edu.ph',
        'total_amount'   => 350.00,
        'payment_method' => 'Cash',
        'payment_status' => 'pending',
        'status'         => 'pending',
        'created_date'   => '2026-05-15',
        'notes'          => '',
        'items'          => [
            ['product_name' => 'PE Uniform Set', 'size' => 'M', 'quantity' => 1, 'subtotal' => 350.00],
        ],
    ],
    [
        'id'             => 'g7h8i9',
        'order_number'   => 'ORD-003',
        'customer_name'  => 'Carlo Mendoza',
        'customer_email' => 'carlo@evsu.edu.ph',
        'total_amount'   => 780.00,
        'payment_method' => 'GCash',
        'payment_status' => 'paid',
        'status'         => 'ready',
        'created_date'   => '2026-05-14',
        'notes'          => '',
        'items'          => [
            ['product_name' => 'Laboratory Gown', 'size' => 'M', 'quantity' => 2, 'subtotal' => 780.00],
        ],
    ],
    [
        'id'             => 'j0k1l2',
        'order_number'   => 'ORD-004',
        'customer_name'  => 'Liza Gomez',
        'customer_email' => 'liza@evsu.edu.ph',
        'total_amount'   => 2100.00,
        'payment_method' => 'Bank Transfer',
        'payment_status' => 'paid',
        'status'         => 'completed',
        'created_date'   => '2026-05-13',
        'notes'          => '',
        'items'          => [
            ['product_name' => 'EVSU Polo Shirt', 'size' => 'S',  'quantity' => 2, 'subtotal' => 900.00],
            ['product_name' => 'PE Uniform Set',  'size' => 'S',  'quantity' => 2, 'subtotal' => 700.00],
            ['product_name' => 'College Pin',     'size' => '',   'quantity' => 2, 'subtotal' => 500.00],
        ],
    ],
    [
        'id'             => 'm3n4o5',
        'order_number'   => 'ORD-005',
        'customer_name'  => 'Mark Villanueva',
        'customer_email' => 'mark@evsu.edu.ph',
        'total_amount'   => 420.00,
        'payment_method' => 'GCash',
        'payment_status' => 'refunded',
        'status'         => 'cancelled',
        'created_date'   => '2026-05-12',
        'notes'          => 'Customer requested cancellation.',
        'items'          => [
            ['product_name' => 'EVSU Polo Shirt', 'size' => 'XL', 'quantity' => 1, 'subtotal' => 420.00],
        ],
    ],
    [
        'id'             => 'p6q7r8',
        'order_number'   => 'ORD-006',
        'customer_name'  => 'Jenny Castro',
        'customer_email' => 'jenny@evsu.edu.ph',
        'total_amount'   => 660.00,
        'payment_method' => 'Cash',
        'payment_status' => 'pending',
        'status'         => 'pending',
        'created_date'   => '2026-05-12',
        'notes'          => '',
        'items'          => [
            ['product_name' => 'PE Uniform Set', 'size' => 'L', 'quantity' => 1, 'subtotal' => 350.00],
            ['product_name' => 'College Pin',    'size' => '',  'quantity' => 1, 'subtotal' => 310.00],
        ],
    ],
    [
        'id'             => 's9t0u1',
        'order_number'   => 'ORD-007',
        'customer_name'  => 'Rico Santos',
        'customer_email' => 'rico@evsu.edu.ph',
        'total_amount'   => 290.00,
        'payment_method' => 'GCash',
        'payment_status' => 'paid',
        'status'         => 'paid',
        'created_date'   => '2026-05-11',
        'notes'          => '',
        'items'          => [
            ['product_name' => 'Laboratory Gown', 'size' => 'L', 'quantity' => 1, 'subtotal' => 290.00],
        ],
    ],
];

$status_flow = ['pending', 'paid', 'processing', 'ready', 'completed', 'cancelled'];

// Pass orders to JS as JSON
$orders_json = json_encode($orders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Order Management — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/CSS/student_dashboard.css"/>
  <link rel="stylesheet" href="/CSS/StaffDashboard.css"/>
  <link rel="stylesheet" href="/CSS/OrderManagement.css"/>
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
        <div class="user-avatar">M</div>
        <div class="user-info">
          <span class="user-name">Maria</span>
          <span class="user-role">Admin</span>
        </div>
      </div>
    </div>
  </header>

  <main class="page-content">

    <!-- Page header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Order Management</h1>
        <p class="page-sub">View and manage all student orders</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="om-filters">
      <div class="om-search-wrap">
        <svg class="om-search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          type="text"
          id="om-search"
          class="om-search"
          placeholder="Search by order # or customer name…"
          oninput="filterOrders()"
        />
      </div>
      <select id="om-status-filter" class="om-select" onchange="filterOrders()">
        <option value="all">All Status</option>
        <?php foreach ($status_flow as $s): ?>
          <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Orders table card -->
    <div class="orders-card" id="om-card">

      <!-- Loading state (hidden by default since data is server-rendered) -->
      <div class="om-loading" id="om-loading" style="display:none;">
        <div class="om-spinner"></div>
        <span>Loading orders…</span>
      </div>

      <!-- Empty state -->
      <div class="empty-state" id="om-empty" style="display:none;">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <rect x="8" y="2" width="8" height="4" rx="1"/>
          <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
        </svg>
        <p class="empty-title">No orders found</p>
        <p class="empty-sub">Try adjusting your search or filter.</p>
      </div>

      <!-- Card header -->
      <div class="orders-card-header">
        <h2 class="orders-title">All Orders</h2>
        <a href="OrderManagement.php" class="view-all-link">View all</a>
      </div>

      <!-- Table -->
      <div class="orders-table-wrap" id="om-table-wrap">
        <table class="orders-table" id="om-table">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Items</th>
              <th>Amount</th>
              <th>Payment</th>
              <th>Status</th>
              <th>Date</th>
              <th>Update Status</th>
            </tr>
          </thead>
          <tbody id="om-tbody">
            <?php foreach ($orders as $order):
              $date_fmt = date('M j', strtotime($order['created_date']));
              $item_count = count($order['items']);
            ?>
            <tr
              class="om-row"
              data-id="<?= htmlspecialchars($order['id']) ?>"
              data-order='<?= htmlspecialchars(json_encode($order), ENT_QUOTES) ?>'
              onclick="openOrderModal(this)"
            >
              <td class="order-num"><?= htmlspecialchars($order['order_number']) ?></td>
              <td>
                <div class="order-student">
                  <div class="student-avatar"><?= strtoupper(substr($order['customer_name'], 0, 1)) ?></div>
                  <span><?= htmlspecialchars($order['customer_name']) ?></span>
                </div>
              </td>
              <td class="text-muted"><?= $item_count ?> item<?= $item_count !== 1 ? 's' : '' ?></td>
              <td class="order-total">₱<?= number_format($order['total_amount'], 2) ?></td>
              <td><span class="badge <?= paymentBadgeClass($order['payment_status']) ?>"><?= ucfirst($order['payment_status']) ?></span></td>
              <td><span class="badge <?= statusBadgeClass($order['status']) ?>"><?= ucfirst($order['status']) ?></span></td>
              <td class="text-muted"><?= $date_fmt ?></td>
              <td onclick="event.stopPropagation()">
                <div class="om-select-wrap">
                  <select
                    class="om-status-select"
                    data-id="<?= htmlspecialchars($order['id']) ?>"
                    onchange="updateStatus(this)"
                  >
                    <?php foreach ($status_flow as $s): ?>
                      <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                       fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"/>
                  </svg>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div><!-- /orders-card -->

  </main>
</div><!-- /main-wrap -->

<!-- ══ ORDER DETAIL MODAL ════════════════════════════════════════════════ -->
<div class="om-modal-backdrop" id="om-modal-backdrop" onclick="closeOrderModal()">
  <div class="om-modal" id="om-modal" onclick="event.stopPropagation()" role="dialog" aria-modal="true" aria-labelledby="om-modal-title">

    <div class="om-modal-header">
      <h2 class="om-modal-title" id="om-modal-title">Order Details</h2>
      <button class="om-modal-close" onclick="closeOrderModal()" aria-label="Close">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    <div class="om-modal-body" id="om-modal-body">
      <!-- Populated by JS -->
    </div>

  </div>
</div>

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Pass PHP data to JS -->
<script>
  const ORDER_DATA = <?= $orders_json ?>;
  const STATUS_FLOW = <?= json_encode($status_flow) ?>;
</script>
<script src="/JS/student_dashboard.js"></script>
<script src="/JS/StaffDashboard.js"></script>
<script src="/JS/OrderManagement.js"></script>
</body>
</html>

<?php
/* ── Badge helper functions ──────────────────────────────────────────── */
function statusBadgeClass(string $status): string {
    return match($status) {
        'completed'  => 'badge-green',
        'pending'    => 'badge-orange',
        'processing' => 'badge-blue',
        'paid'       => 'badge-blue',
        'ready'      => 'badge-purple',
        'cancelled'  => 'badge-red',
        default      => 'badge-gray',
    };
}

function paymentBadgeClass(string $status): string {
    return match($status) {
        'paid'     => 'badge-green',
        'pending'  => 'badge-orange',
        'refunded' => 'badge-gray',
        default    => 'badge-gray',
    };
}
?>