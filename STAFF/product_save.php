<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/product_sizes.php';

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
$image    = trim($raw['image_url'] ?? '');
$active   = !empty($raw['is_active']) ? 1 : 0;
$sizes    = $raw['sizes_available'] ?? [];
$sizesList = is_array($sizes) ? array_values(array_filter(array_map(
    static fn($s) => strtoupper(trim((string) $s)),
    $sizes
))) : [];

$sizeStockRaw = $raw['size_stock'] ?? [];
$sizeStockMap = [];
if (is_array($sizeStockRaw)) {
    foreach ($sizeStockRaw as $size => $qty) {
        $size = strtoupper(trim((string) $size));
        if ($size === '') {
            continue;
        }
        $sizeStockMap[$size] = max(0, (int) $qty);
    }
}

if ($sizesList !== []) {
    $normalized = [];
    foreach ($sizesList as $size) {
        $normalized[$size] = $sizeStockMap[$size] ?? 0;
    }
    $sizeStockMap = $normalized;
    $stock = evsu_size_stock_total($sizeStockMap);
} else {
    $stock = (int) ($raw['stock_quantity'] ?? 0);
    $sizeStockMap = [];
}

$sizeStockJson = evsu_encode_size_stock($sizeStockMap);
$sizesStr      = implode(',', array_keys($sizeStockMap ?: array_fill_keys($sizesList, 0)));

if ($name === '' || $price < 0) {
    echo json_encode(['success' => false, 'message' => 'Name and price are required.']);
    exit;
}

$hasSizeStockCol = function_exists('evsu_column_exists') && evsu_column_exists($conn, 'products', 'size_stock');

if ($id > 0) {
    if ($hasSizeStockCol) {
        $stmt = $conn->prepare(
            'UPDATE products SET sku=?, name=?, description=?, category=?, unit_price=?, markup_price=?,
             stock_quantity=?, size_stock=?, sizes_available=?, image_url=?, is_active=? WHERE id=?'
        );
        $stmt->bind_param(
            'ssssddisssii',
            $sku,
            $name,
            $desc,
            $category,
            $price,
            $markup,
            $stock,
            $sizeStockJson,
            $sizesStr,
            $image,
            $active,
            $id
        );
    } else {
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
    }
} else {
    if ($hasSizeStockCol) {
        $stmt = $conn->prepare(
            'INSERT INTO products (sku, name, description, category, unit_price, markup_price, stock_quantity, size_stock, sizes_available, image_url, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'ssssddisssi',
            $sku,
            $name,
            $desc,
            $category,
            $price,
            $markup,
            $stock,
            $sizeStockJson,
            $sizesStr,
            $image,
            $active
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
}

$ok = $stmt->execute();
$err = $stmt->error;
$newId = $id > 0 ? $id : (int) $conn->insert_id;
$stmt->close();

if ($ok) {
    evsu_log_activity($conn, $id > 0 ? 'Product Updated' : 'Product Added', $_SESSION['user_email'] ?? '', $_SESSION['role'] ?? 'staff', "Product: {$name}");
}

echo json_encode(['success' => $ok, 'id' => $newId, 'message' => $ok ? 'Saved' : $err]);
