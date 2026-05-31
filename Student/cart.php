<?php
/**
 * cart_add.php — AJAX endpoint to add a product to the cart
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/product_sizes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$product_id   = (int)   ($_POST['product_id']   ?? 0);
$product_name = trim(    $_POST['product_name']  ?? '');
$unit_price   = (float) ($_POST['unit_price']    ?? 0);
$size         = trim(    $_POST['size']           ?? '');
$quantity     = max(1, (int)($_POST['quantity']   ?? 1));
$user_id      = (int) $_SESSION['user_id'];

if (!$product_id || !$product_name || $unit_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product data.']);
    exit;
}

$size = strtoupper($size);
$stockInfo = evsu_get_product_size_stock($conn, $product_id, $size);
$bySize = $stockInfo['by_size'];
if ($bySize !== []) {
    if ($size === '') {
        echo json_encode(['success' => false, 'message' => 'Please select a size.']);
        exit;
    }
    $available = (int) ($bySize[$size] ?? 0);
} else {
    $available = (int) $stockInfo['total'];
}

try {
    $pdo = evsu_pdo_connect();

    $stmt = $pdo->prepare(
        'SELECT id, quantity FROM cart_items
         WHERE user_id = ? AND product_id = ? AND size = ? LIMIT 1'
    );
    $stmt->execute([$user_id, $product_id, $size]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $inCart = $existing ? (int) $existing['quantity'] : 0;
    if ($inCart + $quantity > $available) {
        $msg = $bySize !== [] && $size !== ''
            ? "Only {$available} left in size {$size}."
            : "Only {$available} left in stock.";
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    if ($existing) {
        $pdo->prepare('UPDATE cart_items SET quantity = quantity + ? WHERE id = ?')
            ->execute([$quantity, $existing['id']]);
    } else {
        $pdo->prepare(
            'INSERT INTO cart_items (user_id, product_id, product_name, unit_price, size, quantity, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        )->execute([$user_id, $product_id, $product_name, $unit_price, $size, $quantity]);
    }

    $countStmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
    $countStmt->execute([$user_id]);
    $cart_count = (int) $countStmt->fetchColumn();
    $_SESSION['cart_count'] = $cart_count;

    echo json_encode(['success' => true, 'cart_count' => $cart_count]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Could not update cart. Please try again.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Could not add to cart. Please try again.']);
}
