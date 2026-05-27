<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';

$ctx        = evsu_student_init($conn);
$user_id    = $ctx['user_id'];
$first_name = $ctx['first_name'];
$cart_count = $ctx['cart_count'];

$orders = [];
$stmt = $conn->prepare(
    "SELECT o.order_number, o.status, o.payment_status, o.total_amount, o.created_at,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
     FROM orders o
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $row['total_amount'] = (float) $row['total_amount'];
    $orders[] = $row;
}
$stmt->close();

$toast_msg  = $_SESSION['toast_msg'] ?? '';
$toast_type = $_SESSION['toast_type'] ?? 'success';
unset($_SESSION['toast_msg'], $_SESSION['toast_type']);

$status_config = [
    'completed'  => ['label' => 'Completed',  'class' => 'badge-green'],
    'pending'    => ['label' => 'Pending',    'class' => 'badge-orange'],
    'processing' => ['label' => 'Processing', 'class' => 'badge-blue'],
    'ready'      => ['label' => 'Ready',      'class' => 'badge-purple'],
    'paid'       => ['label' => 'Paid',       'class' => 'badge-green'],
    'cancelled'  => ['label' => 'Cancelled',  'class' => 'badge-red'],
];
$active_nav = 'orders';
require __DIR__ . '/_layout_top.php';
?>

    <div class="page-header">
      <div>
        <h1 class="page-title">My Orders</h1>
        <p class="page-sub"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?></p>
      </div>
      <a href="student_product.php" class="btn-shop">Browse Products</a>
    </div>

    <div class="orders-card">
      <div class="orders-card-header">
        <h2 class="orders-title">Order History</h2>
      </div>

      <?php if (empty($orders)): ?>
        <div class="empty-state">
          <p class="empty-title">No orders yet</p>
          <p class="empty-sub">Place an order from your cart to see it here.</p>
          <a href="student_product.php" class="btn-shop btn-shop-sm">Shop Now</a>
        </div>
      <?php else: ?>
        <div class="orders-table-wrap">
          <table class="orders-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Date</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Payment</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $o):
                $sc = $status_config[$o['status']] ?? ['label' => ucfirst($o['status']), 'class' => 'badge-gray'];
              ?>
              <tr>
                <td><?= htmlspecialchars($o['order_number']) ?></td>
                <td><?= htmlspecialchars(date('M j, Y', strtotime($o['created_at']))) ?></td>
                <td><?= (int) $o['item_count'] ?></td>
                <td>₱<?= number_format($o['total_amount'], 2) ?></td>
                <td><span class="badge <?= $sc['class'] ?>"><?= htmlspecialchars($sc['label']) ?></span></td>
                <td><?= htmlspecialchars(ucfirst($o['payment_status'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
