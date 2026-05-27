<?php
session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';

$ctx        = evsu_student_init($conn);
$user_id    = $ctx['user_id'];
$first_name = $ctx['first_name'];
$user_name  = $ctx['user_name'];
$cart_items = [];

$stmt = $conn->prepare(
    'SELECT c.id, c.product_id, c.product_name, c.unit_price, c.quantity, c.size,
            COALESCE(p.image_url, \'\') AS image_url
     FROM cart_items c
     LEFT JOIN products p ON p.id = c.product_id
     WHERE c.user_id = ?
     ORDER BY c.created_at DESC'
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['unit_price'] = (float) $row['unit_price'];
    $row['quantity']   = (int) $row['quantity'];
    $cart_items[] = $row;
}
$stmt->close();

$cart_count = array_sum(array_column($cart_items, 'quantity'));
$total      = array_reduce($cart_items, fn($s, $i) => $s + $i['unit_price'] * $i['quantity'], 0);

// Toast from redirect
$toast_msg  = $_SESSION['toast_msg']  ?? '';
$toast_type = $_SESSION['toast_type'] ?? 'success';
unset($_SESSION['toast_msg'], $_SESSION['toast_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Shopping Cart — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/student_dashboard.css"/>
  <link rel="stylesheet" href="../CSS/student_cart.css"/>
</head>
<body>

<!-- ══ SIDEBAR ══════════════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-top">
    <div class="sidebar-logo">
      <div class="logo-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
      </div>
      <div><span class="logo-name">EVSU</span><span class="logo-sub">RESERVE</span></div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a href="student_dashboard.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Dashboard
    </a>
    <a href="student_product.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      Products
    </a>
    <a href="student_orders.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/></svg>
      My Orders
    </a>
    <a href="student_cart.php" class="nav-item active">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      Cart
      <?php if ($cart_count > 0): ?><span class="nav-badge"><?= $cart_count ?></span><?php endif; ?>
    </a>
    <a href="student_profile.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profile
    </a>
  </nav>
  <div class="sidebar-bottom">
    <a href="../landing_page.php" class="nav-item nav-logout">
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
        <?php if ($cart_count > 0): ?><span class="cart-dot"><?= $cart_count ?></span><?php endif; ?>
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
        <h1 class="page-title">Shopping Cart</h1>
        <p class="page-sub"><?= $cart_count ?> item<?= $cart_count !== 1 ? 's' : '' ?> in your cart</p>
      </div>
    </div>

    <?php if (empty($cart_items)): ?>
      <!-- Empty state -->
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="52" height="52" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="1.4"
             stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
        <p class="empty-title">Your cart is empty</p>
        <p class="empty-sub">Browse products and add items to your cart.</p>
        <a href="student_product.php" class="btn-shop btn-shop-sm">Shop Now</a>
      </div>

    <?php else: ?>

      <div class="cart-layout">

        <!-- ── LEFT: Cart items ── -->
        <div class="cart-items-col">
          <?php foreach ($cart_items as $item): ?>
          <div class="cart-row" id="row-<?= $item['id'] ?>">

            <!-- Thumbnail -->
            <div class="cart-thumb">
              <?php if (!empty($item['image_url'])): ?>
                <img src="<?= htmlspecialchars($item['image_url']) ?>"
                     alt="<?= htmlspecialchars($item['product_name']) ?>"/>
              <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.5"
                     stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                  <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
              <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="cart-info">
              <h3 class="cart-product-name"><?= htmlspecialchars($item['product_name']) ?></h3>
              <?php if (!empty($item['size'])): ?>
                <span class="cart-size-tag">Size: <?= htmlspecialchars($item['size']) ?></span>
              <?php endif; ?>
              <span class="cart-unit-price">₱<?= number_format($item['unit_price'], 2) ?></span>
            </div>

            <!-- Quantity stepper -->
            <div class="qty-stepper">
              <button type="button" class="qty-btn"
                      onclick="changeQty(<?= $item['id'] ?>, -1)"
                      aria-label="Decrease">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>
              </button>
              <span class="qty-val" id="qty-<?= $item['id'] ?>"><?= $item['quantity'] ?></span>
              <button type="button" class="qty-btn"
                      onclick="changeQty(<?= $item['id'] ?>, 1)"
                      aria-label="Increase">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              </button>
            </div>

            <!-- Row total -->
            <span class="cart-row-total" id="subtotal-<?= $item['id'] ?>">
              ₱<?= number_format($item['unit_price'] * $item['quantity'], 2) ?>
            </span>

            <!-- Delete -->
            <button type="button" class="cart-delete-btn"
                    onclick="removeItem(<?= $item['id'] ?>)"
                    aria-label="Remove item">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                   fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6M14 11v6"/>
                <path d="M9 6V4h6v2"/>
              </svg>
            </button>

            <!-- Hidden fields for JS -->
            <span class="d-none"
                  data-price="<?= $item['unit_price'] ?>"
                  data-qty="<?= $item['quantity'] ?>"
                  id="data-<?= $item['id'] ?>">
            </span>
          </div>
          <?php endforeach; ?>
        </div><!-- /cart-items-col -->

        <!-- ── RIGHT: Order summary & checkout ── -->
        <aside class="checkout-panel">

          <h3 class="summary-title">Order Summary</h3>

          <div class="summary-lines">
            <div class="summary-row">
              <span>Subtotal (<?= $cart_count ?> items)</span>
              <span id="display-subtotal">₱<?= number_format($total, 2) ?></span>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-row summary-total">
              <span>Total</span>
              <span class="total-amount" id="display-total">₱<?= number_format($total, 2) ?></span>
            </div>
          </div>

          <!-- Checkout form -->
          <form method="POST" action="online_checkout.php"
                enctype="multipart/form-data" id="checkout-form">

            <!-- Payment method -->
            <div class="form-field">
              <label class="field-label">Payment Method</label>
              <div class="payment-options">
                <label class="pay-opt">
                  <input type="radio" name="payment_method" value="cash" checked
                         onchange="toggleProof(this.value)"/>
                  <span class="pay-opt-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    Cash Payment
                  </span>
                </label>
                <label class="pay-opt">
                  <input type="radio" name="payment_method" value="online"
                         onchange="toggleProof(this.value)"/>
                  <span class="pay-opt-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Online Payment
                  </span>
                </label>
              </div>
            </div>

            <!-- Proof of payment (shown for cash) -->
            <div class="form-field" id="proof-section">
              <label class="field-label">Proof of Payment <span class="field-opt">(optional)</span></label>
              <label class="file-upload-label" id="file-upload-label">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="16 16 12 12 8 16"/>
                  <line x1="12" y1="12" x2="12" y2="21"/>
                  <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>
                </svg>
                <span id="file-name-display">Choose file...</span>
                <input type="file" name="proof_of_payment" accept="image/*"
                       id="proof-input" onchange="updateFileName(this)"/>
              </label>
            </div>

            <!-- Notes -->
            <div class="form-field">
              <label class="field-label">Order Notes <span class="field-opt">(optional)</span></label>
              <textarea name="notes" rows="2" placeholder="Special instructions..."
                        class="notes-input"></textarea>
            </div>

            <!-- Place order -->
            <button type="submit" class="btn-place-order" id="place-order-btn">
              <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                   fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="4" width="22" height="16" rx="2"/>
                <line x1="1" y1="10" x2="23" y2="10"/>
              </svg>
              Place Order
            </button>

          </form>
        </aside>
      </div><!-- /cart-layout -->

    <?php endif; ?>
  </main>
</div>

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<?php if ($toast_msg): ?>
<script>
  window.addEventListener('DOMContentLoaded', () => {
    showToast(<?= json_encode($toast_msg) ?>, <?= json_encode($toast_type) ?>);
  });
</script>
<?php endif; ?>

<!-- Pass cart data to JS -->
<script>
  const CART_DATA = <?= json_encode(array_map(fn($i) => [
      'id'         => $i['id'],
      'unit_price' => $i['unit_price'],
      'quantity'   => $i['quantity'],
  ], $cart_items)) ?>;
</script>

<script src="../JS/student_dashboard.js"></script>
<script src="../JS/student_cart.js"></script>
</body>
</html>