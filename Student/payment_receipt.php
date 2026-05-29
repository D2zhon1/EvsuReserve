<?php
session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';

$ctx        = evsu_student_init($conn);
$user_id    = $ctx['user_id'];
$first_name = $ctx['first_name'];
$cart_count = $ctx['cart_count'];

$payment_id = isset($_GET['payment_id']) ? (int) $_GET['payment_id'] : 0;
if ($payment_id <= 0) {
    $_SESSION['toast_msg'] = 'Payment receipt not found.';
    $_SESSION['toast_type'] = 'error';
    header('Location: student_orders.php');
    exit;
}

$stmt = $conn->prepare(
    'SELECT p.id, p.payment_code, p.method, p.amount, p.created_at, p.status, p.proof_url,
            o.order_number, o.status AS order_status
     FROM payments p
     INNER JOIN orders o ON o.id = p.order_id
     WHERE p.id = ? AND o.user_id = ?
     LIMIT 1'
);
$stmt->bind_param('ii', $payment_id, $user_id);
$stmt->execute();
$receipt = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$receipt) {
    $_SESSION['toast_msg'] = 'You do not have access to this receipt.';
    $_SESSION['toast_type'] = 'error';
    header('Location: student_orders.php');
    exit;
}

$can_upload_receipt = $receipt['method'] === 'Cash'
    && $receipt['order_status'] === 'processing'
    && $receipt['status'] === 'pending'
    && empty($receipt['proof_url']);

$has_proof = !empty($receipt['proof_url']);

$toast_msg  = $_SESSION['toast_msg'] ?? '';
$toast_type = $_SESSION['toast_type'] ?? 'success';
unset($_SESSION['toast_msg'], $_SESSION['toast_type']);

$active_nav = 'orders';
$page_title = 'Payment Receipt';
require __DIR__ . '/_layout_top.php';
?>

<style>
  .receipt-wrap {
    max-width: 760px;
    margin: 0 auto;
  }

  .receipt-card {
    background: #fff;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    border: 1px solid #e8e8ef;
  }

  .receipt-title {
    margin: 0 0 4px;
    font-size: 28px;
    color: #222;
  }

  .receipt-sub {
    margin: 0 0 20px;
    color: #666;
  }

  .receipt-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
  }

  .receipt-item {
    background: #f8f9ff;
    border: 1px solid #e5e9ff;
    border-radius: 10px;
    padding: 12px;
  }

  .receipt-label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6b7280;
    margin-bottom: 4px;
  }

  .receipt-value {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
  }

  .receipt-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 6px;
  }

  .receipt-btn {
    border: 0;
    border-radius: 10px;
    padding: 10px 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
  }

  .receipt-btn-primary {
    background: #4f46e5;
    color: #fff;
  }

  .receipt-btn-muted {
    background: #eef2ff;
    color: #312e81;
  }

  .receipt-upload-box {
    margin-top: 1.25rem;
    padding: 1rem;
    background: #f8f9ff;
    border: 1px dashed #c7d2fe;
    border-radius: 12px;
  }

  .receipt-upload-box h3 {
    margin: 0 0 .35rem;
    font-size: 1rem;
    color: #1f2937;
  }

  .receipt-upload-box p {
    margin: 0 0 .75rem;
    font-size: .88rem;
    color: #6b7280;
  }

  .receipt-upload-form input[type="file"] {
    margin-bottom: .75rem;
    width: 100%;
  }

  .receipt-proof-preview {
    margin-top: 1rem;
    max-width: 320px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
  }

  @media (max-width: 640px) {
    .receipt-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="receipt-wrap">
  <div class="receipt-card">
    <h1 class="receipt-title">Payment Receipt</h1>
    <?php if ($receipt['method'] === 'Cash' && $receipt['order_status'] === 'pending'): ?>
      <p class="receipt-sub">Your order is awaiting staff confirmation. Items remain in your cart until staff approves.</p>
    <?php elseif ($can_upload_receipt): ?>
      <p class="receipt-sub">Staff confirmed your order. Pay at the cashier, then upload your payment receipt below so the cashier can verify your payment.</p>
    <?php elseif ($receipt['method'] === 'Cash' && $has_proof && $receipt['status'] === 'pending'): ?>
      <p class="receipt-sub">Receipt uploaded. Waiting for cashier verification.</p>
    <?php else: ?>
      <p class="receipt-sub">Present this when claiming your order.</p>
    <?php endif; ?>

    <div class="receipt-grid">
      <div class="receipt-item">
        <div class="receipt-label">Payment ID</div>
        <div class="receipt-value"><?= htmlspecialchars($receipt['payment_code']) ?></div>
      </div>
      <div class="receipt-item">
        <div class="receipt-label">Payment Date</div>
        <div class="receipt-value"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($receipt['created_at']))) ?></div>
      </div>
      <div class="receipt-item">
        <div class="receipt-label">Payment Method</div>
        <div class="receipt-value"><?= htmlspecialchars($receipt['method']) ?></div>
      </div>
      <div class="receipt-item">
        <div class="receipt-label">Order Number</div>
        <div class="receipt-value"><?= htmlspecialchars($receipt['order_number']) ?></div>
      </div>
      <div class="receipt-item">
        <div class="receipt-label">Amount</div>
        <div class="receipt-value">PHP <?= number_format((float) $receipt['amount'], 2) ?></div>
      </div>
      <div class="receipt-item">
        <div class="receipt-label">Payment Status</div>
        <div class="receipt-value"><?= htmlspecialchars(ucfirst($receipt['status'])) ?></div>
      </div>
    </div>

    <?php if ($can_upload_receipt): ?>
    <div class="receipt-upload-box">
      <h3>Upload Payment Receipt</h3>
      <p>After paying at the cashier, upload a photo of your receipt here.</p>
      <form class="receipt-upload-form" method="POST" action="upload_payment_receipt.php" enctype="multipart/form-data">
        <input type="hidden" name="payment_id" value="<?= (int) $payment_id ?>"/>
        <input type="file" name="proof_of_payment" accept="image/*" required/>
        <button type="submit" class="receipt-btn receipt-btn-primary">Upload Receipt</button>
      </form>
    </div>
    <?php elseif ($has_proof): ?>
    <div class="receipt-upload-box">
      <h3>Uploaded Receipt</h3>
      <img class="receipt-proof-preview" src="../<?= htmlspecialchars($receipt['proof_url']) ?>" alt="Payment receipt"/>
    </div>
    <?php endif; ?>

    <div class="receipt-actions">
      <button type="button" class="receipt-btn receipt-btn-primary" onclick="window.print()">Print Receipt</button>
      <a href="student_orders.php" class="receipt-btn receipt-btn-muted">Go to My Orders</a>
    </div>
  </div>
</div>

<?php if ($toast_msg): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    alert(<?= json_encode($toast_msg) ?>);
  });
</script>
<?php endif; ?>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
