<?php
/**
 * Order stock adjustment helpers.
 */

require_once __DIR__ . '/product_sizes.php';

function evsu_orders_track_stock_flag(mysqli $conn): bool
{
    return function_exists('evsu_column_exists') && evsu_column_exists($conn, 'orders', 'stock_deducted');
}

function evsu_deduct_order_stock(mysqli $conn, int $order_id): void
{
    $track_flag = evsu_orders_track_stock_flag($conn);

    if ($track_flag) {
        $flag = $conn->prepare('SELECT stock_deducted FROM orders WHERE id = ? LIMIT 1');
        $flag->bind_param('i', $order_id);
        $flag->execute();
        $row = $flag->get_result()->fetch_assoc();
        $flag->close();

        if (!$row || (int) $row['stock_deducted'] === 1) {
            return;
        }
    }

    $items_stmt = $conn->prepare(
        'SELECT product_id, product_name, quantity, COALESCE(size, \'\') AS size FROM order_items WHERE order_id = ?'
    );
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $items_stmt->close();

    if (count($items) === 0) {
        throw new RuntimeException('Order has no items.');
    }

    foreach ($items as $item) {
        $pid = (int) $item['product_id'];
        $qty = (int) $item['quantity'];
        if ($pid <= 0) {
            continue;
        }
        evsu_deduct_product_stock($conn, $pid, $qty, (string) ($item['size'] ?? ''));
    }

    if ($track_flag) {
        $upd = $conn->prepare('UPDATE orders SET stock_deducted = 1 WHERE id = ?');
        $upd->bind_param('i', $order_id);
        $upd->execute();
        $upd->close();
    }
}

function evsu_restore_order_stock(mysqli $conn, int $order_id): void
{
    $track_flag = evsu_orders_track_stock_flag($conn);

    if ($track_flag) {
        $flag = $conn->prepare('SELECT stock_deducted FROM orders WHERE id = ? LIMIT 1');
        $flag->bind_param('i', $order_id);
        $flag->execute();
        $row = $flag->get_result()->fetch_assoc();
        $flag->close();

        if (!$row || (int) $row['stock_deducted'] !== 1) {
            return;
        }
    }

    $items_stmt = $conn->prepare(
        'SELECT product_id, quantity, COALESCE(size, \'\') AS size FROM order_items WHERE order_id = ?'
    );
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $items_stmt->close();

    foreach ($items as $item) {
        $pid = (int) $item['product_id'];
        $qty = (int) $item['quantity'];
        if ($pid <= 0 || $qty <= 0) {
            continue;
        }
        evsu_restore_product_stock($conn, $pid, $qty, (string) ($item['size'] ?? ''));
    }

    if ($track_flag) {
        $upd = $conn->prepare('UPDATE orders SET stock_deducted = 0 WHERE id = ?');
        $upd->bind_param('i', $order_id);
        $upd->execute();
        $upd->close();
    }
}
