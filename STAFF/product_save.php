<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../database.php';

$raw = json_decode(file_get_contents('php://input'), true);
if (!is_array($raw)) {
    $raw = $_POST;
}

$id       = (int) ($raw['id'] ?? 0);
$name     = trim($raw['name'] ?? '');
$desc     = trim($raw['description'] ?? '');
$category = trim($raw['category'] ?? 'general');
$sku      = trim($raw['sku'] ?? '');
$price    = (float) ($raw['price'] ?? 0);
$markup   = (float) ($raw['markup_price'] ?? 0);
if ($markup <= 0) {
    $markup = round($price * 1.2, 2);
}
$stock    = (int) ($raw['stock_quantity'] ?? 0);
$image    = trim($raw['image_url'] ?? '');
$active   = !empty($raw['is_active']) ? 1 : 0;
$sizes    = $raw['sizes_available'] ?? [];
$sizesStr = is_array($sizes) ? implode(',', array_filter($sizes)) : trim((string) $sizes);

if ($name === '' || $price < 0) {
    echo json_encode(['success' => false, 'message' => 'Name and price are required.']);
    exit;
}

if ($id > 0) {
    $stmt = $conn->prepare(
        'UPDATE products SET sku=?, name=?, description=?, category=?, unit_price=?, markup_price=?,
         stock_quantity=?, sizes_available=?, image_url=?, is_active=? WHERE id=?'
    );
    $stmt->bind_param(
        'ssssddissii',
        $sku,
        $name,
        $desc,
        $category,
        $price,
        $markup,
        $stock,
        $sizesStr,
        $image,
        $active,
        $id
    );
} else {
    $stmt = $conn->prepare(
        'INSERT INTO products (sku, name, description, category, unit_price, markup_price, stock_quantity, sizes_available, image_url, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'ssssddissi',
        $sku,
        $name,
        $desc,
        $category,
        $price,
        $markup,
        $stock,
        $sizesStr,
        $image,
        $active
    );
}

$ok = $stmt->execute();
$err = $stmt->error;
$newId = $id > 0 ? $id : (int) $conn->insert_id;
$stmt->close();

if ($ok) {
    evsu_log_activity($conn, $id > 0 ? 'Product Updated' : 'Product Added', $_SESSION['user_email'] ?? '', $_SESSION['role'] ?? 'staff', "Product: {$name}");
}

echo json_encode(['success' => $ok, 'id' => $newId, 'message' => $ok ? 'Saved' : $err]);
