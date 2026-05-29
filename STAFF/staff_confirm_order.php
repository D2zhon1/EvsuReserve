<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../database.php';

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST;
}

$order_id = isset($input['id']) ? (int) $input['id'] : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT o.id, o.user_id, o.status, o.order_number,
            (SELECT p.method FROM payments p WHERE p.order_id = o.id ORDER BY p.id DESC LIMIT 1) AS payment_method
     FROM orders o
     WHERE o.id = ?
     LIMIT 1"
);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    exit;
}

if ($order['status'] !== 'pending' || ($order['payment_method'] ?? '') !== 'Cash') {
    echo json_encode(['success' => false, 'message' => 'This order cannot be confirmed.']);
    exit;
}

$user_id = (int) $order['user_id'];

$items_stmt = $conn->prepare(
    'SELECT product_id, product_name, quantity FROM order_items WHERE order_id = ?'
);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();

if (count($order_items) === 0) {
    echo json_encode(['success' => false, 'message' => 'Order has no items.']);
    exit;
}

$conn->begin_transaction();

try {
    $stock_stmt = $conn->prepare('SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE');
    $deduct_stmt = $conn->prepare(
        'UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?'
    );

    foreach ($order_items as $item) {
        $pid = (int) $item['product_id'];
        $qty = (int) $item['quantity'];

        if ($pid <= 0) {
            continue;
        }

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

    $upd = $conn->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
    $upd->bind_param('i', $order_id);
    $upd->execute();
    $upd->close();

    $del = $conn->prepare('DELETE FROM cart_items WHERE user_id = ?');
    $del->bind_param('i', $user_id);
    $del->execute();
    $del->close();

    $conn->commit();

    evsu_log_activity(
        $conn,
        'Order Confirmed by Staff',
        $_SESSION['user_email'] ?? '',
        $_SESSION['role'] ?? 'staff',
        "Order {$order['order_number']} confirmed for cashier payment."
    );

    echo json_encode([
        'success'      => true,
        'order_id'     => $order_id,
        'order_number' => $order['order_number'],
        'new_status'   => 'processing',
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
