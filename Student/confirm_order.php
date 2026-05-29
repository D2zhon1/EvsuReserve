<?php
session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student_cart.php');
    exit;
}

evsu_require_roles(['student']);
$user_id = (int) $_SESSION['user_id'];

$notes = trim($_POST['notes'] ?? '');

$stmt = $conn->prepare(
    'SELECT id, product_id, product_name, unit_price, size, quantity
     FROM cart_items WHERE user_id = ?'
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (count($cart_items) === 0) {
    $_SESSION['toast_msg']  = 'Your cart is empty.';
    $_SESSION['toast_type'] = 'error';
    header('Location: student_cart.php');
    exit;
}

$total = 0.0;
foreach ($cart_items as $item) {
    $total += (float) $item['unit_price'] * (int) $item['quantity'];
}

$order_number = 'ORD-' . date('Y') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
$chk = $conn->prepare('SELECT id FROM orders WHERE order_number = ? LIMIT 1');
$chk->bind_param('s', $order_number);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    $order_number = 'ORD-' . date('Y') . '-' . str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
}
$chk->close();

$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        'INSERT INTO orders (order_number, user_id, total_amount, status, payment_status, notes)
         VALUES (?, ?, ?, \'pending\', \'pending\', ?)'
    );
    $stmt->bind_param('sids', $order_number, $user_id, $total, $notes);
    $stmt->execute();
    $order_id = (int) $conn->insert_id;
    $stmt->close();

    $item_stmt = $conn->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, size, quantity, unit_price, subtotal)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($cart_items as $item) {
        $subtotal = (float) $item['unit_price'] * (int) $item['quantity'];
        $pid      = (int) $item['product_id'];
        $pname    = $item['product_name'];
        $size     = $item['size'];
        $qty      = (int) $item['quantity'];
        $price    = (float) $item['unit_price'];
        $item_stmt->bind_param('iissidd', $order_id, $pid, $pname, $size, $qty, $price, $subtotal);
        $item_stmt->execute();
    }
    $item_stmt->close();

    $pay_code = 'PAY-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    $pay_stmt = $conn->prepare(
        'INSERT INTO payments (payment_code, order_id, method, amount, status)
         VALUES (?, ?, \'Cash\', ?, \'pending\')'
    );
    $pay_stmt->bind_param('sid', $pay_code, $order_id, $total);
    $pay_stmt->execute();
    $payment_id = (int) $conn->insert_id;
    $pay_stmt->close();

    // Cart is kept until staff confirms the order.
    $conn->commit();

    evsu_log_activity(
        $conn,
        'Order Confirmed (Cash)',
        $_SESSION['user_email'] ?? '',
        'student',
        "Order {$order_number} awaiting staff confirmation."
    );

    $_SESSION['toast_msg']  = 'Order confirmed. Awaiting staff approval. You can upload your receipt after staff confirms.';
    $_SESSION['toast_type'] = 'success';
    header('Location: payment_receipt.php?payment_id=' . $payment_id);
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    $_SESSION['toast_msg']  = 'Could not confirm order: ' . $e->getMessage();
    $_SESSION['toast_type'] = 'error';
    header('Location: student_cart.php');
    exit;
}
