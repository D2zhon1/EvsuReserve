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

$conn->begin_transaction();

try {
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
