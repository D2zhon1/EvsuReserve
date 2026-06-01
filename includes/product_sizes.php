<?php
/**
 * Per-size stock helpers for products (JSON map: {"S":10,"M":5,...}).
 */

function evsu_parse_size_stock(?string $json): array
{
    if ($json === null || trim($json) === '') {
        return [];
    }
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return [];
    }
    $out = [];
    foreach ($data as $size => $qty) {
        $size = strtoupper(trim((string) $size));
        if ($size === '') {
            continue;
        }
        $out[$size] = max(0, (int) $qty);
    }
    return $out;
}

function evsu_encode_size_stock(array $map): string
{
    $clean = [];
    foreach ($map as $size => $qty) {
        $size = strtoupper(trim((string) $size));
        if ($size === '') {
            continue;
        }
        $clean[$size] = max(0, (int) $qty);
    }
    return $clean === [] ? '' : json_encode($clean, JSON_UNESCAPED_UNICODE);
}

function evsu_size_stock_total(array $map): int
{
    return array_sum($map);
}

/** Default threshold for staff low-stock alerts. */
function evsu_low_stock_threshold(): int
{
    return 10;
}

/** Per-size quantities below threshold (e.g. S => 5). */
function evsu_low_stock_sizes(array $sizeStock, int $threshold = 0): array
{
    if ($threshold <= 0) {
        $threshold = evsu_low_stock_threshold();
    }
    $low = [];
    foreach ($sizeStock as $size => $qty) {
        if ((int) $qty < $threshold) {
            $low[strtoupper(trim((string) $size))] = (int) $qty;
        }
    }
    return evsu_sort_size_keys($low);
}

function evsu_sort_size_keys(array $map): array
{
    $order = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL'];
    uksort($map, static function (string $a, string $b) use ($order): int {
        $ia = array_search($a, $order, true);
        $ib = array_search($b, $order, true);
        $ia = $ia === false ? 999 : $ia;
        $ib = $ib === false ? 999 : $ib;
        if ($ia === $ib) {
            return strcmp($a, $b);
        }
        return $ia <=> $ib;
    });
    return $map;
}

/** Whether the product should appear in a low-stock alert list. */
function evsu_product_needs_low_stock_alert(int $stockQty, array $sizeStock, int $threshold = 0): bool
{
    if ($threshold <= 0) {
        $threshold = evsu_low_stock_threshold();
    }
    if ($sizeStock !== []) {
        return evsu_low_stock_sizes($sizeStock, $threshold) !== [];
    }
    return $stockQty < $threshold;
}

/** Human-readable low-size summary: "S: 5 · M: 2". */
function evsu_format_low_sizes_text(array $lowSizes): string
{
    if ($lowSizes === []) {
        return '';
    }
    $parts = [];
    foreach ($lowSizes as $size => $qty) {
        $parts[] = $size . ': ' . $qty;
    }
    return implode(' · ', $parts);
}

function evsu_product_has_size_stock(mysqli $conn, int $productId): bool
{
    if (!function_exists('evsu_column_exists') || !evsu_column_exists($conn, 'products', 'size_stock')) {
        return false;
    }
    $stmt = $conn->prepare('SELECT size_stock FROM products WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $map = evsu_parse_size_stock($row['size_stock'] ?? null);
    return count($map) > 0;
}

function evsu_get_product_size_stock(mysqli $conn, int $productId, string $size = ''): array
{
    $cols = 'stock_quantity, sizes_available';
    if (function_exists('evsu_column_exists') && evsu_column_exists($conn, 'products', 'size_stock')) {
        $cols .= ', size_stock';
    }

    $stmt = $conn->prepare("SELECT {$cols} FROM products WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['total' => 0, 'by_size' => [], 'size_qty' => 0];
    }

    $bySize = evsu_parse_size_stock($row['size_stock'] ?? null);
    $size   = strtoupper(trim($size));

    if ($bySize !== []) {
        return [
            'total'    => evsu_size_stock_total($bySize),
            'by_size'  => $bySize,
            'size_qty' => $size !== '' ? ($bySize[$size] ?? 0) : evsu_size_stock_total($bySize),
        ];
    }

    $total = (int) ($row['stock_quantity'] ?? 0);
    return ['total' => $total, 'by_size' => [], 'size_qty' => $total];
}

function evsu_deduct_product_stock(mysqli $conn, int $productId, int $qty, string $size = ''): void
{
    if ($qty <= 0 || $productId <= 0) {
        return;
    }

    $size = strtoupper(trim($size));

    $stmt = $conn->prepare(
        'SELECT stock_quantity, size_stock FROM products WHERE id = ? FOR UPDATE'
    );
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new RuntimeException('Product not found.');
    }

    $bySize = evsu_parse_size_stock($row['size_stock'] ?? null);

    if ($bySize !== [] && $size !== '') {
        $available = $bySize[$size] ?? 0;
        if ($available < $qty) {
            throw new RuntimeException("Size {$size} does not have enough stock. Available: {$available}");
        }
        $bySize[$size] = $available - $qty;
        $json  = evsu_encode_size_stock($bySize);
        $total = evsu_size_stock_total($bySize);

        $upd = $conn->prepare(
            'UPDATE products SET size_stock = ?, stock_quantity = ? WHERE id = ?'
        );
        $upd->bind_param('sii', $json, $total, $productId);
        $upd->execute();
        if ($upd->affected_rows < 0) {
            throw new RuntimeException('Failed to update size stock.');
        }
        $upd->close();
        return;
    }

    $current = (int) ($row['stock_quantity'] ?? 0);
    if ($current < $qty) {
        throw new RuntimeException('Not enough stock. Available: ' . $current);
    }

    $upd = $conn->prepare(
        'UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?'
    );
    $upd->bind_param('iii', $qty, $productId, $qty);
    $upd->execute();
    if ($upd->affected_rows <= 0) {
        throw new RuntimeException('Failed to deduct stock.');
    }
    $upd->close();
}

function evsu_restore_product_stock(mysqli $conn, int $productId, int $qty, string $size = ''): void
{
    if ($qty <= 0 || $productId <= 0) {
        return;
    }

    $size = strtoupper(trim($size));

    $stmt = $conn->prepare('SELECT stock_quantity, size_stock FROM products WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return;
    }

    $bySize = evsu_parse_size_stock($row['size_stock'] ?? null);

    if ($bySize !== [] && $size !== '') {
        $bySize[$size] = ($bySize[$size] ?? 0) + $qty;
        $json  = evsu_encode_size_stock($bySize);
        $total = evsu_size_stock_total($bySize);

        $upd = $conn->prepare('UPDATE products SET size_stock = ?, stock_quantity = ? WHERE id = ?');
        $upd->bind_param('sii', $json, $total, $productId);
        $upd->execute();
        $upd->close();
        return;
    }

    $upd = $conn->prepare('UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?');
    $upd->bind_param('ii', $qty, $productId);
    $upd->execute();
    $upd->close();
}
