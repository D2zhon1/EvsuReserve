<?php

require_once __DIR__ . '/mail.php';

function evsu_notify_student_order_completed(mysqli $conn, int $orderId): bool
{
    $stmt = $conn->prepare(
        'SELECT o.order_number, o.total_amount, u.full_name, u.email
         FROM orders o
         INNER JOIN users u ON u.id = o.user_id
         WHERE o.id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        return false;
    }

    $email = trim((string) ($order['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("EVSU Reserve: no valid email for order #{$orderId}");
        return false;
    }

    $items = [];
    $istmt = $conn->prepare(
        'SELECT product_name, COALESCE(size, \'\') AS size, quantity FROM order_items WHERE order_id = ?'
    );
    $istmt->bind_param('i', $orderId);
    $istmt->execute();
    $ires = $istmt->get_result();
    while ($row = $ires->fetch_assoc()) {
        $items[] = $row;
    }
    $istmt->close();

    return evsu_send_order_completed_email(
        $email,
        $order['full_name'],
        $order['order_number'],
        (float) $order['total_amount'],
        $items
    );
}
