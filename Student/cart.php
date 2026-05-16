<?php
/**
 * cart_add.php — AJAX endpoint to add a product to the cart
 * Called by products.js via fetch()
 */
session_start();
header('Content-Type: application/json');

// ── DB config ─────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'evsu_reserve');
define('DB_USER', 'root');
define('DB_PASS', '');
// ─────────────────────────────────────────────────────────────────────────

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
$user_id      = $_SESSION['user_id'];

if (!$product_id || !$product_name || $unit_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product data.']);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Check if same product+size already in cart
    $stmt = $pdo->prepare(
        "SELECT id, quantity FROM cart_items
         WHERE user_id = ? AND product_id = ? AND size = ? LIMIT 1"
    );
    $stmt->execute([$user_id, $product_id, $size]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Increment quantity
        $pdo->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id = ?")
            ->execute([$quantity, $existing['id']]);
    } else {
        // Insert new cart item
        $pdo->prepare(
            "INSERT INTO cart_items (user_id, product_id, product_name, unit_price, size, quantity, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        )->execute([$user_id, $product_id, $product_name, $unit_price, $size, $quantity]);
    }

    // Get updated cart count
    $countStmt = $pdo->prepare("SELECT SUM(quantity) AS total FROM cart_items WHERE user_id = ?");
    $countStmt->execute([$user_id]);
    $cart_count = (int)($countStmt->fetchColumn() ?? 0);
    $_SESSION['cart_count'] = $cart_count;

    echo json_encode(['success' => true, 'cart_count' => $cart_count]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}