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

$payment_method = $_POST['payment_method'] ?? 'cash';
$notes          = trim($_POST['notes'] ?? '');

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
    // Lock product rows and verify enough stock before creating the order.
    $stock_stmt = $conn->prepare('SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE');
    $deduct_stmt = $conn->prepare(
        'UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?'
    );

    foreach ($cart_items as $item) {
        $pid = (int) $item['product_id'];
        $qty = (int) $item['quantity'];

        $stock_stmt->bind_param('i', $pid);
        $stock_stmt->execute();
        $stock_res = $stock_stmt->get_result()->fetch_assoc();
        $current_stock = (int) ($stock_res['stock_quantity'] ?? 0);

        if ($current_stock < $qty) {
            throw new RuntimeException(
                $item['product_name'] . ' does not have enough stock. Available: ' . $current_stock
            );
        }

        $deduct_stmt->bind_param('iii', $qty, $pid, $qty);
        $deduct_stmt->execute();
        if ($deduct_stmt->affected_rows <= 0) {
            throw new RuntimeException('Failed to reserve stock for ' . $item['product_name']);
        }
    }

    $stock_stmt->close();
    $deduct_stmt->close();

    $stmt = $conn->prepare(
        'INSERT INTO orders (order_number, user_id, total_amount, status, payment_status, notes)
         VALUES (?, ?, ?, \'pending\', \'pending\', ?)'
    );
    $stmt->bind_param('sids', $order_number, $user_id, $total, $notes);
    $stmt->execute();
    $order_id = (int) $conn->insert_id;
    $stmt->close();

    if (function_exists('evsu_column_exists') && evsu_column_exists($conn, 'orders', 'stock_deducted')) {
        $flag_stmt = $conn->prepare('UPDATE orders SET stock_deducted = 1 WHERE id = ?');
        $flag_stmt->bind_param('i', $order_id);
        $flag_stmt->execute();
        $flag_stmt->close();
    }

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

    $pay_method = ($payment_method === 'online') ? 'GCash' : 'Cash';
    $pay_code   = 'PAY-' . str_pad((string) random_int(1, 9999), 3, '0', STR_PAD_LEFT);

    $pay_stmt = $conn->prepare(
        'INSERT INTO payments (payment_code, order_id, method, amount, status)
         VALUES (?, ?, ?, ?, \'pending\')'
    );
    $pay_stmt->bind_param('sisd', $pay_code, $order_id, $pay_method, $total);
    $pay_stmt->execute();
    $payment_id = (int) $conn->insert_id;
    $pay_stmt->close();

    $del = $conn->prepare('DELETE FROM cart_items WHERE user_id = ?');
    $del->bind_param('i', $user_id);
    $del->execute();
    $del->close();

    $conn->commit();

    evsu_log_activity(
        $conn,
        'Order Placed',
        $_SESSION['user_email'] ?? '',
        'student',
        "Order {$order_number} placed. Total: ₱" . number_format($total, 2)
    );

    $_SESSION['cart_count'] = 0;
    $_SESSION['toast_msg']  = "Order {$order_number} placed successfully!";
    $_SESSION['toast_type'] = 'success';
    header('Location: payment_receipt.php?payment_id=' . $payment_id);
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    $_SESSION['toast_msg']  = 'Checkout failed: ' . $e->getMessage();
    $_SESSION['toast_type'] = 'error';
    header('Location: student_cart.php');
    exit;
}
