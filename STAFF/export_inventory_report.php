<?php
session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/product_sizes.php';

$lowStockThreshold = evsu_low_stock_threshold();

$stats = $conn->query(
    "SELECT
        COUNT(*) AS total_orders,
        SUM(status = 'pending') AS pending_orders,
        SUM(status = 'processing') AS processing_orders,
        SUM(status = 'completed') AS completed_orders,
        SUM(CASE WHEN payment_status IN ('verified','paid') THEN total_amount ELSE 0 END) AS total_revenue
     FROM orders"
)->fetch_assoc();

$total_orders      = (int) ($stats['total_orders'] ?? 0);
$pending_orders    = (int) ($stats['pending_orders'] ?? 0);
$processing_orders = (int) ($stats['processing_orders'] ?? 0);
$completed_orders  = (int) ($stats['completed_orders'] ?? 0);
$total_revenue     = (float) ($stats['total_revenue'] ?? 0);

$recent_orders = [];
$res = $conn->query(
    "SELECT o.order_number AS order_no, u.full_name AS student, o.status,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS items,
            DATE(o.created_at) AS order_date, o.total_amount AS total, o.payment_status AS payment
     FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC
     LIMIT 50"
);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $row['total'] = (float) $row['total'];
        $row['items'] = (int) $row['items'];
        $recent_orders[] = $row;
    }
}

$low_stock_products = [];
$res = $conn->query(
    "SELECT id, name, COALESCE(sku, CONCAT('SKU-', id)) AS sku, stock_quantity, size_stock
     FROM products WHERE is_active = 1 ORDER BY name"
);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $sizeStock = evsu_parse_size_stock($row['size_stock'] ?? null);
        $stock = $sizeStock !== []
            ? evsu_size_stock_total($sizeStock)
            : (int) ($row['stock_quantity'] ?? 0);

        if (!evsu_product_needs_low_stock_alert($stock, $sizeStock, $lowStockThreshold)) {
            continue;
        }

        $lowSizes = evsu_low_stock_sizes($sizeStock, $lowStockThreshold);
        $low_stock_products[] = [
            'name'      => $row['name'],
            'sku'       => $row['sku'],
            'stock'     => $stock,
            'low_sizes' => $lowSizes,
        ];
    }
}

$filename = 'inventory_report_' . date('Ymd_His') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "<html><head><meta charset=\"UTF-8\"></head><body>";
echo "<h2>EVSU Reserve - Staff Inventory Report</h2>";
echo "<p>Generated: " . htmlspecialchars(date('Y-m-d H:i:s')) . "</p>";

echo "<h3>Summary</h3>";
echo "<table border='1' cellspacing='0' cellpadding='5'>";
echo "<tr><th>Total Orders</th><th>Pending</th><th>Processing</th><th>Completed</th><th>Total Revenue</th></tr>";
echo "<tr>";
echo "<td>{$total_orders}</td>";
echo "<td>{$pending_orders}</td>";
echo "<td>{$processing_orders}</td>";
echo "<td>{$completed_orders}</td>";
echo "<td>" . number_format($total_revenue, 2) . "</td>";
echo "</tr>";
echo "</table>";

echo "<h3>Recent Orders</h3>";
echo "<table border='1' cellspacing='0' cellpadding='5'>";
echo "<tr><th>Order #</th><th>Student</th><th>Status</th><th>Payment</th><th>Items</th><th>Total</th><th>Date</th></tr>";
foreach ($recent_orders as $order) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($order['order_no']) . "</td>";
    echo "<td>" . htmlspecialchars($order['student']) . "</td>";
    echo "<td>" . htmlspecialchars((string) $order['status']) . "</td>";
    echo "<td>" . htmlspecialchars((string) $order['payment']) . "</td>";
    echo "<td>" . (int) $order['items'] . "</td>";
    echo "<td>" . number_format((float) $order['total'], 2) . "</td>";
    echo "<td>" . htmlspecialchars($order['order_date']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Low Stock Products</h3>";
echo "<table border='1' cellspacing='0' cellpadding='5'>";
echo "<tr><th>Product</th><th>SKU</th><th>Stock</th><th>Low Sizes</th></tr>";
foreach ($low_stock_products as $product) {
    $sizeParts = [];
    foreach (($product['low_sizes'] ?? []) as $size => $qty) {
        $sizeParts[] = $size . ': ' . (int) $qty;
    }

    echo "<tr>";
    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
    echo "<td>" . htmlspecialchars($product['sku']) . "</td>";
    echo "<td>" . (int) $product['stock'] . "</td>";
    echo "<td>" . htmlspecialchars(implode(', ', $sizeParts)) . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</body></html>";

