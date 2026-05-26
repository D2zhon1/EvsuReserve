<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../database.php';

$raw = json_decode(file_get_contents('php://input'), true);
if (!is_array($raw)) {
    $raw = $_POST;
}

$id = (int) ($raw['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product.']);
    exit;
}

$stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
$stmt->bind_param('i', $id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok]);
