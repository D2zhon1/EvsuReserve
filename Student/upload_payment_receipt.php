<?php
session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/uploads.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student_orders.php');
    exit;
}

$ctx     = evsu_student_init($conn);
$user_id = $ctx['user_id'];

$payment_id = isset($_POST['payment_id']) ? (int) $_POST['payment_id'] : 0;
if ($payment_id <= 0) {
    $_SESSION['toast_msg']  = 'Invalid payment.';
    $_SESSION['toast_type'] = 'error';
    header('Location: student_orders.php');
    exit;
}

$stmt = $conn->prepare(
    'SELECT p.id, p.proof_url, p.method, p.status AS payment_status,
            o.status AS order_status, o.user_id
     FROM payments p
     INNER JOIN orders o ON o.id = p.order_id
     WHERE p.id = ? AND o.user_id = ?
     LIMIT 1'
);
$stmt->bind_param('ii', $payment_id, $user_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    $_SESSION['toast_msg']  = 'Payment not found.';
    $_SESSION['toast_type'] = 'error';
    header('Location: student_orders.php');
    exit;
}

if ($payment['method'] !== 'Cash' || $payment['order_status'] !== 'processing') {
    $_SESSION['toast_msg']  = 'Receipt upload is not available for this order yet.';
    $_SESSION['toast_type'] = 'error';
    header('Location: payment_receipt.php?payment_id=' . $payment_id);
    exit;
}

if (!empty($payment['proof_url'])) {
    $_SESSION['toast_msg']  = 'Receipt already uploaded.';
    $_SESSION['toast_type'] = 'success';
    header('Location: payment_receipt.php?payment_id=' . $payment_id);
    exit;
}

$proof_url = null;
if (!empty($_FILES['proof_of_payment']['name'])) {
    $proof_url = evsu_save_payment_proof($_FILES['proof_of_payment']);
}

if ($proof_url === null) {
    if (evsu_upload_failure_reason() === 'quota') {
        $_SESSION['toast_msg'] = 'Upload storage is full (5 GB limit). Please contact an administrator.';
    } else {
        $_SESSION['toast_msg'] = 'Please upload a valid receipt image (JPEG, PNG, WebP, or GIF, max 5 MB).';
    }
    $_SESSION['toast_type'] = 'error';
    header('Location: payment_receipt.php?payment_id=' . $payment_id);
    exit;
}

$upd = $conn->prepare('UPDATE payments SET proof_url = ? WHERE id = ?');
$upd->bind_param('si', $proof_url, $payment_id);
$ok = $upd->execute();
$upd->close();

if ($ok) {
    evsu_log_activity(
        $conn,
        'Payment Receipt Uploaded',
        $_SESSION['user_email'] ?? '',
        'student',
        "Receipt uploaded for payment #{$payment_id}"
    );
    $_SESSION['toast_msg']  = 'Receipt uploaded. Cashier can now verify your payment.';
    $_SESSION['toast_type'] = 'success';
} else {
    $_SESSION['toast_msg']  = 'Failed to save receipt.';
    $_SESSION['toast_type'] = 'error';
}

header('Location: payment_receipt.php?payment_id=' . $payment_id);
exit;
