<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/order_stock.php';

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST;
}

$id     = isset($input['id']) ? (int) $input['id'] : 0;
$status = strtolower(trim((string) ($input['status'] ?? '')));

$allowed = ['pending', 'paid', 'processing', 'ready', 'completed', 'cancelled'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$stmt = $conn->prepare('SELECT id, status, order_number FROM orders WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

$old_status = $order['status'];

if ($old_status === $status) {
    echo json_encode(['success' => true, 'status' => $status]);
    exit;
}

$payment_status = null;
if ($status === 'paid') {
    $payment_status = 'paid';
} elseif ($status === 'completed') {
    $payment_status = 'verified';
} elseif ($status === 'cancelled') {
    $payment_status = 'rejected';
}

$conn->begin_transaction();

try {
    if ($status === 'completed' && $old_status !== 'completed') {
        evsu_deduct_order_stock($conn, $id);
    }

    if ($status === 'cancelled' && $old_status === 'completed') {
        evsu_restore_order_stock($conn, $id);
    }

    if ($payment_status !== null) {
        $stmt = $conn->prepare('UPDATE orders SET status = ?, payment_status = ? WHERE id = ?');
        $stmt->bind_param('ssi', $status, $payment_status, $id);
    } else {
        $stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $id);
    }

    $stmt->execute();
    $ok = $stmt->affected_rows >= 0;
    $stmt->close();

    if (!$ok) {
        throw new RuntimeException('Failed to update order status.');
    }

    $conn->commit();

    evsu_log_activity(
        $conn,
        'Order Status Updated',
        $_SESSION['user_email'] ?? '',
        $_SESSION['role'] ?? 'staff',
        "Order {$order['order_number']} status: {$old_status} → {$status}"
    );

    echo json_encode(['success' => true, 'status' => $status, 'payment_status' => $payment_status]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
