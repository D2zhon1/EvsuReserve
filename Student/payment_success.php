<?php
session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';

$ctx = evsu_student_init($conn);

$user_id = $ctx['user_id'];

//
// GET CART
//

$stmt = $conn->prepare("
    SELECT *
    FROM cart_items
    WHERE user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$cart_items = [];
$total = 0;

while ($row = $result->fetch_assoc()) {

    $cart_items[] = $row;

    $total +=
    $row['unit_price'] * $row['quantity'];
}

$stmt->close();

//
// CREATE ORDER
//

$order_number =
"ORD-" . time();

$status = "paid";

$payment_method = "online";

$notes =
$_SESSION['pending_order']['notes'] ?? '';

$stmt = $conn->prepare("
INSERT INTO orders
(
    user_id,
    order_number,
    total_amount,
    payment_method,
    payment_status,
    notes
)
VALUES
(?,?,?,?,?,?)
");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}   
$stmt->bind_param(
    "isdsss",
    $user_id,
    $order_number,
    $total,
    $payment_method,
    $status,
    $notes
);

$stmt->execute();

$order_id = $stmt->insert_id;

$stmt->close();

//
// INSERT ORDER ITEMS
//

foreach ($cart_items as $item) {

    $stmt = $conn->prepare("
    INSERT INTO order_items
    (
        order_id,
        product_id,
        product_name,
        quantity,
        unit_price,
        size
    )
    VALUES
    (?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "iisids",
        $order_id,
        $item['product_id'],
        $item['product_name'],
        $item['quantity'],
        $item['unit_price'],
        $item['size']
    );

    $stmt->execute();

    $stmt->close();
}

//
// CLEAR CART
//

$stmt = $conn->prepare("
DELETE FROM cart_items
WHERE user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$stmt->close();

unset($_SESSION['pending_order']);
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