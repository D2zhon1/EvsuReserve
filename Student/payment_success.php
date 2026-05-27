<?php
session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';

$ctx = evsu_student_init($conn);

$user_id = $ctx['user_id'];

// Prevent duplicate order creation when this page is refreshed.
if (!empty($_SESSION['online_order_done']) && !empty($_SESSION['last_order_number'])) {
    $created_order_number = (string) $_SESSION['last_order_number'];
} else {
    $stmt = $conn->prepare(
        'SELECT id, product_id, product_name, unit_price, size, quantity
         FROM cart_items
         WHERE user_id = ?'
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart_items = [];
    $total = 0.0;
    while ($row = $result->fetch_assoc()) {
        $row['unit_price'] = (float) $row['unit_price'];
        $row['quantity'] = (int) $row['quantity'];
        $cart_items[] = $row;
        $total += $row['unit_price'] * $row['quantity'];
    }
    $stmt->close();

    if (empty($cart_items)) {
        $_SESSION['toast_msg'] = 'Cart is empty. No order was created.';
        $_SESSION['toast_type'] = 'error';
        header('Location: student_cart.php');
        exit;
    }

    $notes = $_SESSION['pending_order']['notes'] ?? '';
    $created_order_number = 'ORD-' . date('Y') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    $pay_code = 'PAY-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

    $conn->begin_transaction();
    try {
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
                throw new RuntimeException('Failed to deduct stock for ' . $item['product_name']);
            }
        }
        $stock_stmt->close();
        $deduct_stmt->close();

        $stmt = $conn->prepare(
            'INSERT INTO orders (order_number, user_id, total_amount, status, payment_status, notes)
             VALUES (?, ?, ?, \'paid\', \'paid\', ?)'
        );
        $stmt->bind_param('sids', $created_order_number, $user_id, $total, $notes);
        $stmt->execute();
        $order_id = (int) $conn->insert_id;
        $stmt->close();

        $item_stmt = $conn->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, size, quantity, unit_price, subtotal)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($cart_items as $item) {
            $pid = (int) $item['product_id'];
            $name = $item['product_name'];
            $size = (string) ($item['size'] ?? '');
            $qty = (int) $item['quantity'];
            $price = (float) $item['unit_price'];
            $subtotal = $price * $qty;
            $item_stmt->bind_param('iissidd', $order_id, $pid, $name, $size, $qty, $price, $subtotal);
            $item_stmt->execute();
        }
        $item_stmt->close();

        $pay_stmt = $conn->prepare(
            'INSERT INTO payments (payment_code, order_id, method, amount, status)
             VALUES (?, ?, \'GCash\', ?, \'verified\')'
        );
        $pay_stmt->bind_param('sid', $pay_code, $order_id, $total);
        $pay_stmt->execute();
        $pay_stmt->close();

        $del_stmt = $conn->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $del_stmt->bind_param('i', $user_id);
        $del_stmt->execute();
        $del_stmt->close();

        $conn->commit();

        $_SESSION['cart_count'] = 0;
        $_SESSION['online_order_done'] = true;
        $_SESSION['last_order_number'] = $created_order_number;
        unset($_SESSION['pending_order']);
    } catch (Throwable $e) {
        $conn->rollback();
        $_SESSION['toast_msg'] = 'Online payment order failed: ' . $e->getMessage();
        $_SESSION['toast_type'] = 'error';
        header('Location: student_cart.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Success</title>

    <style>

        body{
            font-family:Arial;
            background:#f5f5f5;
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
        }

        .card{
            background:white;
            padding:40px;
            border-radius:12px;
            text-align:center;
            width:400px;
        }

        .count{
            font-size:40px;
            color:#6c5ce7;
            font-weight:bold;
        }

    </style>
</head>
<body>

<div class="card">

    <h1>✅ Payment Successful</h1>

    <p>Your order has been placed.</p>

    <p>
        Redirecting in
    </p>

    <div class="count" id="count">
        5
    </div>

</div>

<script>

let seconds = 5;

const el =
document.getElementById("count");

const timer =
setInterval(() => {

    seconds--;

    el.innerHTML = seconds;

    if(seconds <= 0){

        clearInterval(timer);

        window.location.href =
        "student_orders.php";
    }

}, 1000);

</script>

</body>
</html>