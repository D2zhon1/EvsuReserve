<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$item_id = (int) ($_POST['item_id'] ?? 0);
$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $item_id, $user_id);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($cart_count);
$stmt->fetch();
$stmt->close();

$_SESSION['cart_count'] = (int) $cart_count;
echo json_encode(['success' => true, 'cart_count' => (int) $cart_count]);
