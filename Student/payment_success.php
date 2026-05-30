<?php
session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';

$ctx = evsu_student_init($conn);

$user_id = $ctx['user_id'];
$pending_order = $_SESSION['pending_order'] ?? null;
if (!$pending_order || ($pending_order['payment_method'] ?? '') !== 'online') {
    header('Location: student_cart.php');
    exit;
}

//
// GET CART
//
$stmt = $conn->prepare(
    'SELECT id, product_id, product_name, unit_price, size, quantity
     FROM cart_items
     WHERE user_id = ?'
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (count($cart_items) === 0) {
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

$notes = trim((string) ($pending_order['notes'] ?? ''));
$reference_number = trim((string) ($_GET['session_id'] ?? $pending_order['reference_number'] ?? ''));
$online_method = trim((string) ($pending_order['online_method'] ?? 'GCash'));
if (!in_array($online_method, ['GCash', 'PayMaya', 'Bank Transfer'], true)) {
    $online_method = 'GCash';
}

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

        $item_stmt->bind_param(
            'iissidd',
            $order_id,
            $pid,
            $pname,
            $size,
            $qty,
            $price,
            $subtotal
        );

        $item_stmt->execute();
    }

    $item_stmt->close();

    $pay_code = 'PAY-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

    $pay_stmt = $conn->prepare(
        'INSERT INTO payments (payment_code, order_id, method, amount, status, reference_number)
         VALUES (?, ?, ?, ?, \'pending\', ?)'
    );

    $pay_stmt->bind_param(
        'sisds',
        $pay_code,
        $order_id,
        $online_method,
        $total,
        $reference_number
    );

    $pay_stmt->execute();
    $pay_stmt->close();

    $del = $conn->prepare('DELETE FROM cart_items WHERE user_id = ?');
    $del->bind_param('i', $user_id);
    $del->execute();
    $del->close();

    $conn->commit();

    unset($_SESSION['pending_order']);

} catch (Throwable $e) {

    $conn->rollback();

    $_SESSION['toast_msg'] = 'Online checkout failed: ' . $e->getMessage();
    $_SESSION['toast_type'] = 'error';

    header('Location: student_cart.php');
    exit;
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