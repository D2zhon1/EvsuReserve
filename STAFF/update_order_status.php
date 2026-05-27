<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../database.php';

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST;
}

$id     = isset($input['id']) ? (int) $input['id'] : 0;
$status = $input['status'] ?? '';

$allowed = ['pending', 'paid', 'processing', 'ready', 'completed', 'cancelled'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
$stmt->bind_param('si', $status, $id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    evsu_log_activity(
        $conn,
        'Order Status Updated',
        $_SESSION['user_email'] ?? '',
        $_SESSION['role'] ?? 'staff',
        "Order #{$id} status set to {$status}"
    );
}

echo json_encode(['success' => $ok]);
