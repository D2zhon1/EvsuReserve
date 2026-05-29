<?php
/**
 * Shared payment queries for cashier pages.
 */

function evsu_fetch_payments(mysqli $conn, string $status_filter = 'all', string $search = ''): array
{
    $sql = "SELECT p.payment_code AS id, p.order_id, o.order_number, u.full_name AS payer_name,
                   p.method, p.amount, p.status, p.reference_number, p.proof_url,
                   p.created_at AS created_date
            FROM payments p
            JOIN orders o ON o.id = p.order_id
            JOIN users u ON u.id = o.user_id
            WHERE 1=1";
    $types  = '';
    $params = [];

    if ($status_filter !== 'all') {
        $sql     .= ' AND p.status = ?';
        $types   .= 's';
        $params[] = $status_filter;
    }

    if ($search !== '') {
        $sql     .= ' AND (u.full_name LIKE ? OR o.order_number LIKE ? OR p.reference_number LIKE ? OR p.payment_code LIKE ?)';
        $types   .= 'ssss';
        $like     = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= ' ORDER BY p.created_at DESC';

    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as &$row) {
        $row['amount'] = (float) $row['amount'];
    }

    return $rows;
}

function evsu_verify_payment(mysqli $conn, string $payment_code, string $action, ?int $verifier_id): bool
{
    if (!in_array($action, ['verified', 'rejected'], true)) {
        return false;
    }

    $stmt = $conn->prepare('SELECT order_id, method, proof_url FROM payments WHERE payment_code = ? LIMIT 1');
    $stmt->bind_param('s', $payment_code);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        return false;
    }

    $order_id = (int) $payment['order_id'];

    if ($action === 'verified' && $payment['method'] === 'Cash' && empty($payment['proof_url'])) {
        return false;
    }

    if ($action === 'verified') {
        $stmt = $conn->prepare(
            "UPDATE payments SET status = 'verified', verified_by = ?, verification_date = NOW()
             WHERE payment_code = ?"
        );
        $stmt->bind_param('is', $verifier_id, $payment_code);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "UPDATE orders SET payment_status = 'verified', status = 'paid' WHERE id = ?"
        );
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare(
            "UPDATE payments SET status = 'rejected', verified_by = ?, verification_date = NOW()
             WHERE payment_code = ?"
        );
        $stmt->bind_param('is', $verifier_id, $payment_code);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "UPDATE orders SET payment_status = 'rejected' WHERE id = ?"
        );
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $stmt->close();
    }

    return true;
}
